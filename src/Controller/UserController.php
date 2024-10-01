<?php

namespace App\Controller;

use App\Form\AboutYouFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $form = $this->createForm(AboutYouFormType::class, $user);
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
}
