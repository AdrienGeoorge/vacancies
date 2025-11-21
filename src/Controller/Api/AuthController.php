<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        #[CurrentUser] $user
    ): JsonResponse {
        if (!$user) {
            return new JsonResponse([
                'message' => 'Identifiants incorrects.'
            ], 401);
        }

        // Ce code NE SERA PAS exécuté si LexikJWT est actif
        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
        ]);
    }
}
