<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        #[CurrentUser] $user
    ): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'message' => 'Vos identifiants sont incorrects.'
            ], 401);
        }

        // Ce code NE DEVRAIT PAS ÊTRE exécuté comme LexikJWT est actif
        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/register', name: '_register', methods: ['POST'])]
    public function register(Request                $request, UserPasswordHasherInterface $userPasswordHasher,
                             ManagerRegistry $managerRegistry, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['firstname'], $data['lastname'])) {
            return new JsonResponse(['message' => 'Tous les champs sont requis pour compléter l\'inscription.'], 400);
        }

        $user = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($user) {
            return new JsonResponse(['message' => 'Cette adresse mail est déjà utilisée.'], 409);
        }

        $user = (new User())
            ->setEmail($data['email'])
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setRoles(['ROLE_USER']);

        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));

        $user->setUsername(strtr(utf8_decode(
            strtolower($user->getFirstname() . $user->getLastname() . substr(bin2hex(random_bytes(3)), 0, 5))
        ), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));

        $managerRegistry->getManager()->persist($user);
        $managerRegistry->getManager()->flush();

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token
        ], 201);
    }
}
