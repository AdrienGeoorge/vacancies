<?php

namespace App\Controller\Api;

use App\Entity\Country;
use App\Entity\Trip;
use App\Entity\User;
use App\Entity\UserBadges;
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

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
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

            $lastTripObject = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser(), true);
            $nextTripObject = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser(), true);

            /** @var User $user */
            return $this->json([
                'user' => $user,
                'passedTrips' => count($passedTrips),
                'countPassedCountries' => $countPassedCountries,
                'countryMostVisited' => $countryMostVisited,
                'percentCountries' => round(($countPassedCountries / $nbCountries) * 100, 2),
                'badges' => $badges,
                'lastTrip' => $lastTripObject ? [
                    'country' => $lastTripObject->getCountry()->getName(),
                    'countDays' => $this->tripService->countDaysBeforeOrAfter($lastTripObject)
                ] : null,
                'nextTrip' => $nextTripObject ? [
                    'country' => $nextTripObject->getCountry()->getName(),
                    'countDays' => $this->tripService->countDaysBeforeOrAfter($nextTripObject)
                ] : null
            ]);
        } else {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }
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
                'biography' => $user->getBiography()
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
                'biography' => $user->getBiography()
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
}
