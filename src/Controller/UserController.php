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

    #[Route('/about', name: 'about')]
    public function home(Request $request): Response
    {
        if ($this->getUser()->getFirstname() && $this->getUser()->getLastname()) return $this->redirectToRoute('app_home');

        $user = $this->getUser();
        $form = $this->createForm(AboutYouType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->managerRegistry->getManager()->persist($user);
            $this->managerRegistry->getManager()->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/about.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/profile', name: 'profile')]
    #[Route('/profile/{user}', name: 'profile_for_user')]
    public function profile(?string $user): Response
    {
        if ($user) {
            $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $user]);
        } else {
            $user = $this->getUser();
        }

        $passedTrips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($user);
        $countPassedCountries = $this->managerRegistry->getRepository(Trip::class)->countPassedCountries($user);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'passedTrips' => count($passedTrips),
            'countPassedCountries' => $countPassedCountries
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
                'privateProfile' => $user->isPrivateProfile()
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
            'status' => $user->isPrivateProfile() ? 'waiting' : 'followed'
        ]);
    }
}
