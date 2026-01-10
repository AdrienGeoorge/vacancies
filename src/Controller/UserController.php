<?php

namespace App\Controller;

use App\Entity\Follows;
use App\Entity\Trip;
use App\Entity\User;
use App\Form\AboutYouType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user', name: 'user_')]
class UserController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/profile', name: 'profile')]
    #[Route('/profile/{username}', name: 'profile_for_user')]
    public function profile(?string $username): Response
    {

        if ($username) {
            $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $username]);
        } else {
            $user = $this->getUser();
        }

        $passedTrips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($user);
        $countPassedCountries = $this->managerRegistry->getRepository(Trip::class)->countPassedCountries($user);
        $countryMostVisited = $this->managerRegistry->getRepository(Trip::class)->getCountryMostVisited($user);

        /** @var User $user */
        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'passedTrips' => count($passedTrips),
            'countPassedCountries' => $countPassedCountries,
            'countryMostVisited' => $countryMostVisited
        ]);
    }

    #[Route('/follow/{user}', name: 'follow', options: ['expose' => true], methods: ['POST'])]
    public function followUser(Request $request, User $user): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) return new JsonResponse([], 500);

        $follow = $this->managerRegistry->getRepository(Follows::class)->findOneBy([
            'followedBy' => $this->getUser(),
            'follower' => $user
        ]);

        if ($follow) {
            $this->managerRegistry->getManager()->remove($follow);
            $this->managerRegistry->getManager()->flush();
            return new JsonResponse([
                'status' => 'deleted',
                'privateProfile' => $user->isPrivateProfile(),
                'follows' => $user->getApprovedFollows()->count(),
                'followedBy' => $user->getApprovedFollowedBy()->count()
            ]);
        }

        $follow = (new Follows())
            ->setFollowedBy($this->getUser())
            ->setFollower($user)
            ->setCreatedAt(new \DateTime());

        if (!$user->isPrivateProfile()) $follow->setIsApproved(true);

        $this->managerRegistry->getManager()->persist($follow);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse([
            'status' => $user->isPrivateProfile() ? 'waiting' : 'followed',
            'follows' => $user->getApprovedFollows()->count(),
            'followedBy' => $user->getApprovedFollowedBy()->count()
        ]);
    }

    #[Route('/change-visibility', name: 'change_visibility', options: ['expose' => true], methods: ['POST'])]
    public function changeVisibility(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) return new JsonResponse([], 500);

        $user = $this->managerRegistry->getRepository(User::class)->find($request->request->get('userId'));
        if (!$user) return new JsonResponse([], 500);
        if ($user !== $this->getUser()) return new JsonResponse([], 403);

        $user->setPrivateProfile(!$user->isPrivateProfile());
        $this->managerRegistry->getManager()->persist($user);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse([
            'private' => $user->isPrivateProfile()
        ]);
    }
}
