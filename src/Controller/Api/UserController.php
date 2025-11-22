<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user', name: 'api_')]
class UserController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): Response
    {
        return $this->json([
            'email' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles(),
        ]);
    }
}
