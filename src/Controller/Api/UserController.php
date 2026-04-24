<?php

namespace App\Controller\Api;

use App\Entity\Country;
use App\Entity\Currency;
use App\Entity\Trip;
use App\Entity\User;
use App\Entity\UserBadges;
use App\Service\FileUploaderService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/user', name: 'api_user_')]
class UserController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;
    private FileUploaderService $uploaderService;
    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService, FileUploaderService $uploaderService, TranslatorInterface $translator)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
        $this->uploaderService = $uploaderService;
        $this->translator = $translator;
    }

    #[Route('/visited-countries', name: 'visited_countries', options: ['expose' => true], methods: ['GET'])]
    public function visitedCountries(Request $request): JsonResponse
    {
        $username = $request->query->get('username');

        if ($username) {
            $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $username]);

            if (!$user) {
                return new JsonResponse(['message' => $this->translator->trans('user.not_found')], Response::HTTP_NOT_FOUND);
            }
        } else {
            /** @var User $user */
            $user = $this->getUser();
        }

        $countries = $this->managerRegistry->getRepository(Trip::class)->getVisitedCountries($user);

        return $this->json($countries);
    }

    #[Route('/next-countries', name: 'next_countries', options: ['expose' => true], methods: ['GET'])]
    public function nextCountries(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $countries = $this->managerRegistry->getRepository(Trip::class)->getNextCountries($user);

        return $this->json($countries);
    }

    #[Route('/me', name: 'me', options: ['expose' => true], methods: ['GET'])]
    public function me(): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        /** @var User $user */
        $user = $this->getUser();
        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'completeName' => $user->getCompleteName(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'biography' => $user->getBiography(),
                'theme' => $user->getTheme(),
                'language' => $user->getLanguage(),
                'homeTimezone' => $user->getHomeTimezone(),
                'preferredCurrency' => $user->getPreferredCurrency() ? [
                    'code' => $user->getPreferredCurrency()->getCode(),
                    'name' => $user->getPreferredCurrency()->getName(),
                    'symbol' => $user->getPreferredCurrency()->getSymbol(),
                ] : null,
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/profile/{username}', name: 'profile_for_user')]
    public function profile(?string $username): Response
    {
        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user) {
            $passedTrips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($user);
            $countPassedCountries = $this->managerRegistry->getRepository(Trip::class)->countPassedCountries($user);
            $countryMostVisited = $this->managerRegistry->getRepository(Trip::class)->getCountryMostVisited($user);
            $nbCountries = $this->managerRegistry->getRepository(Country::class)->count();
            $badges = $this->managerRegistry->getRepository(UserBadges::class)->findBy(['user' => $user]);

            $lastTrip = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser(), true);
            $nextTrip = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser(), true);

            $uniqueUserIds = [];
            $uniqueNames = [];
            $soloCount = 0;
            $groupCount = 0;

            foreach ($passedTrips as $trip) {
                $companions = 0;
                foreach ($trip->getTripTravelers() as $traveler) {
                    if ($traveler->getInvited() && $traveler->getInvited()->getId() === $user->getId()) {
                        continue;
                    }

                    $companions++;
                    if ($traveler->getInvited()) {
                        $uniqueUserIds[$traveler->getInvited()->getId()] = true;
                    } else {
                        $uniqueNames[$traveler->getName()] = true;
                    }
                }

                if ($companions === 0) {
                    $soloCount++;
                } else {
                    $groupCount++;
                }
            }

            $countUniqueTravelers = count($uniqueUserIds) + count($uniqueNames);

            /** @var User $user */
            return $this->json([
                'user' => $user,
                'passedTrips' => count($passedTrips),
                'countPassedCountries' => $countPassedCountries,
                'countryMostVisited' => $countryMostVisited,
                'percentCountries' => round(($countPassedCountries / $nbCountries) * 100, 2),
                'badges' => $badges,
                'lastTrip' => $lastTrip ? [
                    'countryCodes' => array_values(array_unique(array_map(fn($d) => $d['country']['code'], $lastTrip['destinations']))),
                    'countDays' => $this->tripService->countDaysBeforeOrAfter($lastTrip['trip'])
                ] : null,
                'nextTrip' => $nextTrip ? [
                    'countryCodes' => array_values(array_unique(array_map(fn($d) => $d['country']['code'], $nextTrip['destinations']))),
                    'countDays' => $this->tripService->countDaysBeforeOrAfter($nextTrip['trip'])
                ] : null,
                'countUniqueTravelers' => $countUniqueTravelers,
                'soloTrips' => $soloCount,
                'groupTrips' => $groupCount,
            ]);
        } else {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/settings/avatar', name: 'update_avatar', options: ['expose' => true], methods: ['POST'])]
    public function updateAvatar(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $avatar = $request->files->get('avatar');
        if (!$avatar) {
            return new JsonResponse(['message' => $this->translator->trans('user.avatar.required')], Response::HTTP_BAD_REQUEST);
        }

        if ($avatar->getSize() > 3 * 1024 * 1024) {
            return new JsonResponse(['message' => $this->translator->trans('user.avatar.max_size')], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($avatar->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
            return new JsonResponse(['message' => $this->translator->trans('user.avatar.invalid_format')], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $directory = $this->getParameter('avatar_directory');
            $fileName = $this->uploaderService->upload($avatar, null, $directory);
            $user->setAvatar($directory . '/' . $fileName);

            $this->managerRegistry->getManager()->persist($user);
            $this->managerRegistry->getManager()->flush();

            $token = $jwtManager->create($user);

            return new JsonResponse([
                'token' => $token,
                'user' => $this->serializeUser($user),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => $this->translator->trans('user.avatar.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/settings/banner', name: 'update_banner', options: ['expose' => true], methods: ['POST'])]
    public function updateBanner(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $banner = $request->files->get('banner');
        if (!$banner) {
            return new JsonResponse(['message' => $this->translator->trans('user.banner.required')], Response::HTTP_BAD_REQUEST);
        }

        if ($banner->getSize() > 8 * 1024 * 1024) {
            return new JsonResponse(['message' => $this->translator->trans('user.banner.max_size')], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($banner->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
            return new JsonResponse(['message' => $this->translator->trans('user.banner.invalid_format')], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $directory = $this->getParameter('banner_directory');
            $fileName = $this->uploaderService->upload($banner, null, $directory);
            $user->setBanner($directory . '/' . $fileName);

            $this->managerRegistry->getManager()->persist($user);
            $this->managerRegistry->getManager()->flush();

            $token = $jwtManager->create($user);

            return new JsonResponse([
                'token' => $token,
                'user' => $this->serializeUser($user),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => $this->translator->trans('user.banner.error')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/settings/theme', name: 'update_theme', options: ['expose' => true], methods: ['POST'])]
    public function updateTheme(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        if (!$data['theme'] && !in_array($data['theme'], ['light', 'dark', 'system'])) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.theme.error')
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setTheme($data['theme']);
        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/settings/personal-data', name: 'update_personal_data', options: ['expose' => true], methods: ['POST'])]
    public function updatePersonalData(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        if (!isset($data['firstname']) || !isset($data['lastname']) || !isset($data['email'])) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.settings.missing_fields')
            ], Response::HTTP_BAD_REQUEST);
        }

        $userEmail = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($userEmail && $userEmail->getId() !== $user->getId()) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.settings.email_taken')
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setFirstName($data['firstname'])
            ->setLastName($data['lastname'])
            ->setEmail($data['email'])
            ->setBiography($data['biography'] ?? null);

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/settings/password', name: 'update_password', options: ['expose' => true], methods: ['POST'])]
    public function updatePassword(Request $request, JWTTokenManagerInterface $jwtManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        if (!isset($data['current']) || !isset($data['password']) || !isset($data['passwordRepeat'])) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.settings.missing_fields')
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['current'])) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.settings.current_password_wrong')
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($data['password'] !== $data['passwordRepeat']) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.settings.passwords_dont_match')
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/settings/notifications', name: 'update_notifications', options: ['expose' => true], methods: ['POST'])]
    public function updateNotifications(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();
        $user->setReceiveReminderEmails($data['receiveReminderEmails'] ?? true);
        $user->setReceiveSummaryEmails($data['receiveSummaryEmails'] ?? true);

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => array_merge($this->serializeUser($user), [
                'receiveReminderEmails' => $user->isReceiveReminderEmails(),
                'receiveSummaryEmails' => $user->isReceiveSummaryEmails(),
            ]),
        ], Response::HTTP_CREATED);
    }

    #[Route('/settings/payment-handles', name: 'update_payment_handles', methods: ['POST'])]
    public function updatePaymentHandles(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();
        $user->setPaypalHandle(!empty($data['paypalHandle']) ? $data['paypalHandle'] : null);
        $user->setRevolutHandle(!empty($data['revolutHandle']) ? $data['revolutHandle'] : null);

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/settings/disabled', name: 'disabled_account', options: ['expose' => true], methods: ['POST'])]
    public function disabledAccount(): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        /** @var User $user */
        $user = $this->getUser();
        $user->setDisabled(new \DateTime());

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse([]);
    }

    #[Route('/settings/language', name: 'update_language', methods: ['POST'])]
    public function updateLanguage(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? null;

        if (!$language || !in_array($language, ['fr', 'en'], true)) {
            return new JsonResponse([
                'message' => $this->translator->trans('user.language.invalid')
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setLanguage($language);

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'message' => $this->translator->trans('user.language.updated'),
        ]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        /** @var User $user */
        $user = $this->getUser();
        $passedTrips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($user);

        $totalDays = 0;
        $totalSpent = 0;
        $totalOnSite = 0;
        $byYear = [];
        $monthCounts = array_fill(1, 12, 0);

        $mostExpensiveTrip = null;
        $cheapestTrip = null;
        $longestTrip = null;
        $shortestTrip = null;

        $and = $this->translator->trans('stats.destinations.and');

        $tripSummary = function (Trip $trip, float|int $value) use ($and): array {
            $names = array_values(array_filter(
                $trip->getDestinations()->map(fn($d) => $d->getCountry()?->getName() ?? '')->toArray()
            ));

            if (count($names) === 0) $label = $trip->getName();
            elseif (count($names) === 1) $label = $names[0];
            elseif (count($names) === 2) $label = $names[0] . $and . $names[1];
            else $label = implode(', ', array_slice($names, 0, -1)) . $and . end($names);

            return [
                'id' => $trip->getId(),
                'name' => $trip->getName(),
                'destinations' => $label,
                'departureDate' => $trip->getDepartureDate()?->format('Y-m-d'),
                'returnDate' => $trip->getReturnDate()?->format('Y-m-d'),
                'total' => $value,
            ];
        };

        foreach ($passedTrips as $trip) {
            $nbTravelers = max(1, $trip->getTripTravelers()->count());

            $days = 0;
            if ($trip->getDepartureDate() && $trip->getReturnDate()) {
                $days = $trip->getDepartureDate()->diff($trip->getReturnDate())->days + 1;
                $totalDays += $days;
            }

            $budget = $this->tripService->getBudget($trip, $user->getPreferredCurrency());
            $tripTotal = $budget['total'];
            $totalSpent += $tripTotal / $nbTravelers;

            $costPerTraveler = round($tripTotal / $nbTravelers, 2);

            $onSite = $this->tripService->getOnSiteExpensePrice($trip, $user->getPreferredCurrency());
            $totalOnSite += round($onSite / $nbTravelers, 2);

            $year = $trip->getDepartureDate()?->format('Y');
            if ($year) {
                $byYear[$year] ??= ['year' => $year, 'trips' => 0, 'days' => 0, 'spent' => 0];
                $byYear[$year]['trips']++;
                $byYear[$year]['days'] += $days;
                $byYear[$year]['spent'] += round($tripTotal / $nbTravelers, 2);
            }

            $month = (int)($trip->getDepartureDate()?->format('n') ?? 0);
            if ($month) {
                $monthCounts[$month]++;
            }

            if ($costPerTraveler > 0) {
                if ($mostExpensiveTrip === null || $costPerTraveler > $mostExpensiveTrip['total']) {
                    $mostExpensiveTrip = $tripSummary($trip, $costPerTraveler);
                }
                if ($cheapestTrip === null || $costPerTraveler < $cheapestTrip['total']) {
                    $cheapestTrip = $tripSummary($trip, $costPerTraveler);
                }
            }

            if ($days > 0) {
                if ($longestTrip === null || $days > $longestTrip['total']) {
                    $longestTrip = $tripSummary($trip, $days);
                }
                if ($shortestTrip === null || $days < $shortestTrip['total']) {
                    $shortestTrip = $tripSummary($trip, $days);
                }
            }
        }

        $nbTrips = count($passedTrips);
        ksort($byYear);

        arsort($monthCounts);
        $preferredMonthNum = array_key_first($monthCounts);
        $preferredMonth = $monthCounts[$preferredMonthNum] > 0
            ? [
                'month' => $preferredMonthNum,
                'label' => $this->translator->trans('stats.month.' . $preferredMonthNum),
                'count' => $monthCounts[$preferredMonthNum],
            ]
            : null;

        $seasonMap = [
            'winter' => [12, 1, 2],
            'spring' => [3, 4, 5],
            'summer' => [6, 7, 8],
            'autumn' => [9, 10, 11],
        ];
        $seasonCounts = [];
        foreach ($seasonMap as $season => $months) {
            $seasonCounts[$season] = array_sum(array_map(fn($m) => $monthCounts[$m], $months));
        }
        arsort($seasonCounts);
        $preferredSeasonKey = array_key_first($seasonCounts);
        $preferredSeason = $seasonCounts[$preferredSeasonKey] > 0
            ? [
                'season' => $this->translator->trans('stats.season.' . $preferredSeasonKey),
                'count' => $seasonCounts[$preferredSeasonKey],
            ]
            : null;

        return $this->json([
            'totalDays' => $totalDays,
            'totalSpent' => round($totalSpent, 2),
            'avgTripDuration' => $nbTrips > 0 ? round($totalDays / $nbTrips, 1) : 0,
            'avgPerTrip' => $nbTrips > 0 ? round($totalSpent / $nbTrips, 2) : 0,
            'avgPerDay' => $totalDays > 0 ? round($totalOnSite / $totalDays, 2) : 0,
            'avgOnSitePerTrip' => $nbTrips > 0 ? round($totalOnSite / $nbTrips, 2) : 0,
            'byYear' => array_values($byYear),
            'mostExpensiveTrip' => $mostExpensiveTrip,
            'cheapestTrip' => $cheapestTrip,
            'longestTrip' => $longestTrip,
            'shortestTrip' => $shortestTrip,
            'preferredMonth' => $preferredMonth,
            'preferredSeason' => $preferredSeason,
            'userCurrency' => $user->getPreferredCurrency()?->getSymbol(),
        ]);
    }

    #[Route('/settings/currency', name: 'update_currency', methods: ['POST'])]
    public function updateCurrency(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);
        $code = $data['currencyCode'] ?? null;

        /** @var User $user */
        $user = $this->getUser();

        if ($code === null) {
            $user->setPreferredCurrency(null);
        } else {
            $currency = $this->managerRegistry->getRepository(Currency::class)->find($code);
            if (!$currency) {
                return new JsonResponse([
                    'message' => $this->translator->trans('user.currency.invalid')
                ], Response::HTTP_BAD_REQUEST);
            }
            $user->setPreferredCurrency($currency);
        }

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'message' => $this->translator->trans('user.currency.updated'),
        ]);
    }

    #[Route('/settings/timezone', name: 'update_timezone', methods: ['POST'])]
    public function updateTimezone(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);
        $timezone = $data['homeTimezone'] ?? null;

        if ($timezone !== null) {
            try {
                new \DateTimeZone($timezone);
            } catch (\Exception) {
                return new JsonResponse([
                    'message' => $this->translator->trans('user.timezone.invalid')
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setHomeTimezone($timezone);

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'message' => $this->translator->trans('user.timezone.updated'),
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'completeName' => $user->getCompleteName(),
            'username' => $user->getUsername(),
            'avatar' => $user->getAvatar(),
            'biography' => $user->getBiography(),
            'theme' => $user->getTheme(),
            'language' => $user->getLanguage(),
            'homeTimezone' => $user->getHomeTimezone(),
            'preferredCurrency' => $user->getPreferredCurrency() ? [
                'code' => $user->getPreferredCurrency()->getCode(),
                'name' => $user->getPreferredCurrency()->getName(),
                'symbol' => $user->getPreferredCurrency()->getSymbol(),
            ] : null,
        ];
    }
}
