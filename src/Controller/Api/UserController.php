<?php

namespace App\Controller\Api;

use App\Entity\Accommodation;
use App\Entity\Activity;
use App\Entity\Country;
use App\Entity\OnSiteExpense;
use App\Entity\Transport;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\User;
use App\Entity\UserBadges;
use App\Entity\VariousExpensive;
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

#[Route('/api/user', name: 'api_user_')]
class UserController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;
    private FileUploaderService $uploaderService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService, FileUploaderService $uploaderService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
        $this->uploaderService = $uploaderService;
    }

    #[Route('/visited-countries', name: 'visited_countries', options: ['expose' => true], methods: ['GET'])]
    public function visitedCountries(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

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
                'theme' => $user->getTheme()
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
                    'country' => $this->tripService->formateDestinationsForString($lastTrip['destinations']),
                    'countDays' => $this->tripService->countDaysBeforeOrAfter($lastTrip['trip'])
                ] : null,
                'nextTrip' => $nextTrip ? [
                    'country' => $this->tripService->formateDestinationsForString($nextTrip['destinations']),
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
            return new JsonResponse(['message' => 'Vous devez ajouter une photo de profil.'], Response::HTTP_BAD_REQUEST);
        }

        if ($avatar->getSize() > 3 * 1024 * 1024) {
            return new JsonResponse(['message' => 'La photo de profil doit faire au maximum 3MB.'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($avatar->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
            return new JsonResponse(['message' => 'La photo de profil doit être au format JPG, GIF ou PNG.'], Response::HTTP_BAD_REQUEST);
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
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'completeName' => $user->getCompleteName(),
                    'username' => $user->getUsername(),
                    'avatar' => $user->getAvatar(),
                    'biography' => $user->getBiography()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Une erreur est survenue lors du changement de la photo de profil.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/settings/banner', name: 'update_banner', options: ['expose' => true], methods: ['POST'])]
    public function updateBanner(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $banner = $request->files->get('banner');
        if (!$banner) {
            return new JsonResponse(['message' => 'Vous devez ajouter une bannière.'], Response::HTTP_BAD_REQUEST);
        }

        if ($banner->getSize() > 8 * 1024 * 1024) {
            return new JsonResponse(['message' => 'La bannière doit faire au maximum 8MB.'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($banner->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
            return new JsonResponse(['message' => 'La bannière doit être au format JPG, GIF ou PNG.'], Response::HTTP_BAD_REQUEST);
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
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'completeName' => $user->getCompleteName(),
                    'username' => $user->getUsername(),
                    'avatar' => $user->getAvatar(),
                    'biography' => $user->getBiography()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Une erreur est survenue lors du changement de la bannière.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/settings/theme', name: 'update_theme', options: ['expose' => true], methods: ['POST'])]
    public function updateTheme(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if (!$this->getUser()) return new JsonResponse([], Response::HTTP_UNAUTHORIZED);

        $data = json_decode($request->getContent(), true);

        if (!$data['theme'] && !in_array($data['theme'], ['light', 'dark', 'system'])) {
            return new JsonResponse([
                'message' => 'Une erreur est survenue lors du changement de thème.'
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
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'completeName' => $user->getCompleteName(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'biography' => $user->getBiography(),
                'theme' => $user->getTheme()
            ]
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
                'message' => 'Vous devez remplir les champs obligatoires.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $userEmail = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($userEmail && $userEmail->getId() !== $user->getId()) {
            return new JsonResponse([
                'message' => 'Cette adresse e-mail est déjà utilisée.'
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
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'completeName' => $user->getCompleteName(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'biography' => $user->getBiography(),
                'theme' => $user->getTheme()
            ]
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
                'message' => 'Vous devez remplir les champs obligatoires.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['current'])) {
            return new JsonResponse([
                'message' => 'Le mot de passe actuel est incorrect.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($data['password'] !== $data['passwordRepeat']) {
            return new JsonResponse([
                'message' => 'Les mots de passe ne correspondent pas.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'completeName' => $user->getCompleteName(),
                'username' => $user->getUsername(),
                'avatar' => $user->getAvatar(),
                'biography' => $user->getBiography(),
                'theme' => $user->getTheme()
            ]
        ], Response::HTTP_CREATED);
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

        $tripSummary = function (Trip $trip, float|int $value): array {
            $names = array_values(array_filter(
                $trip->getDestinations()->map(fn($d) => $d->getCountry()?->getName() ?? '')->toArray()
            ));

            if (count($names) === 0) $label = $trip->getName();
            elseif (count($names) === 1) $label = $names[0];
            elseif (count($names) === 2) $label = $names[0] . ' et ' . $names[1];
            else $label = implode(', ', array_slice($names, 0, -1)) . ' et ' . end($names);

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

            $budget = $this->tripService->getBudget($trip);
            $tripTotal = $budget['total'];
            $totalSpent += $tripTotal / $nbTravelers;

            $costPerTraveler = round($tripTotal / $nbTravelers, 2);

            $onSite = $this->tripService->getOnSiteExpensePrice($trip);
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

            // Voyage le plus / moins cher (coût par voyageur)
            if ($costPerTraveler > 0) {
                if ($mostExpensiveTrip === null || $costPerTraveler > $mostExpensiveTrip['total']) {
                    $mostExpensiveTrip = $tripSummary($trip, $costPerTraveler);
                }
                if ($cheapestTrip === null || $costPerTraveler < $cheapestTrip['total']) {
                    $cheapestTrip = $tripSummary($trip, $costPerTraveler);
                }
            }

            // Voyage le plus long / plus court
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

        // Mois préféré (le plus de départs)
        arsort($monthCounts);
        $preferredMonthNum = array_key_first($monthCounts);
        $monthLabels = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $preferredMonth = $monthCounts[$preferredMonthNum] > 0
            ? ['month' => $preferredMonthNum, 'label' => $monthLabels[$preferredMonthNum], 'count' => $monthCounts[$preferredMonthNum]]
            : null;

        // Saison préférée
        $seasonMap = [
            'Hiver' => [12, 1, 2],
            'Printemps' => [3, 4, 5],
            'Été' => [6, 7, 8],
            'Automne' => [9, 10, 11],
        ];
        $seasonCounts = [];
        foreach ($seasonMap as $season => $months) {
            $seasonCounts[$season] = array_sum(array_map(fn($m) => $monthCounts[$m], $months));
        }
        arsort($seasonCounts);
        $preferredSeasonName = array_key_first($seasonCounts);
        $preferredSeason = $seasonCounts[$preferredSeasonName] > 0
            ? ['season' => $preferredSeasonName, 'count' => $seasonCounts[$preferredSeasonName]]
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
        ]);
    }
}
