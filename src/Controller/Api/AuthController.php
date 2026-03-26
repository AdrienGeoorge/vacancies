<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $firstname = strip_tags($data['firstname']);
        $lastname = strip_tags($data['lastname']);

        if ($firstname !== $data['firstname'] || $lastname !== $data['lastname']) {
            return new JsonResponse(['message' => 'Les balises HTML ne sont pas autorisées.'], 400);
        }

        $user = (new User())
            ->setEmail($data['email'])
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setRoles(['ROLE_USER']);

        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));

        $user->setUsername(strtr(mb_convert_encoding(strtolower($user->getFirstname() . $user->getLastname() . substr(bin2hex(random_bytes(3)), 0, 5)), 'ISO-8859-1', 'UTF-8'), mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'ISO-8859-1', 'UTF-8'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));

        $managerRegistry->getManager()->persist($user);
        $managerRegistry->getManager()->flush();

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

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        $client = $clientRegistry->getClient('google');
        $authorizationUrl = $client->getOAuth2Provider()->getAuthorizationUrl([
            'scope' => ['openid', 'profile', 'email']
        ]);

        return new JsonResponse(['authUrl' => $authorizationUrl]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $client = $clientRegistry->getClient('google');

        try {
            // IMPORTANT : Désactiver la vérification du state pour l'architecture SPA
            $accessToken = $client->getAccessToken([
                'code' => $request->query->get('code')
            ]);

            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUserFromToken($accessToken);

            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();

            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstname($googleUser->getFirstName());
                $user->setLastname($googleUser->getLastName());
                $user->setGoogleId($googleId);
                $user->setRoles(['ROLE_USER']);

                // Générer un mot de passe aléatoire (l'utilisateur n'en aura pas besoin)
                $randomPassword = bin2hex(random_bytes(32));
                $user->setPassword($passwordHasher->hashPassword($user, $randomPassword));

                $user->setUsername(strtr(mb_convert_encoding(strtolower($user->getFirstname() . $user->getLastname() . substr(bin2hex(random_bytes(3)), 0, 5)), 'ISO-8859-1', 'UTF-8'), mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'ISO-8859-1', 'UTF-8'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));
            } else {
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

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
        } catch (\Exception) {
            return new JsonResponse([
                'message' => 'Erreur lors de la connexion avec Google',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
