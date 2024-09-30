<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_')]
class AppController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        if (!$this->getUser()) return $this->redirectToRoute('auth_login');

        return $this->render('home/index.html.twig');
    }
}
