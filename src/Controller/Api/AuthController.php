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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface                                    $translator,
        #[Autowire(env: 'GOOGLE_IOS_CLIENT_ID')] private readonly string        $googleIosClientId,
        #[Autowire(env: 'GOOGLE_IOS_REDIRECT_URI')] private readonly string     $googleIosRedirectUri,
        #[Autowire(env: 'GOOGLE_ANDROID_CLIENT_ID')] private readonly string    $googleAndroidClientId,
        #[Autowire(env: 'GOOGLE_ANDROID_REDIRECT_URI')] private readonly string $googleAndroidRedirectUri,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        #[CurrentUser] $user
    ): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'message' => $this->translator->trans('auth.login.invalid')
            ], 401);
        }

        // Ce code NE DEVRAIT PAS ÊTRE exécuté comme LexikJWT est actif
        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/register', name: '_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,
                             ManagerRegistry $managerRegistry, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['firstname'], $data['lastname'])) {
            return new JsonResponse(['message' => $this->translator->trans('auth.register.missing_fields')], 400);
        }

        $user = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($user) {
            return new JsonResponse(['message' => $this->translator->trans('auth.register.email_taken')], 409);
        }

        $firstname = strip_tags($data['firstname']);
        $lastname = strip_tags($data['lastname']);

        if ($firstname !== $data['firstname'] || $lastname !== $data['lastname']) {
            return new JsonResponse(['message' => $this->translator->trans('auth.register.html_forbidden')], 400);
        }

        $user = (new User())
            ->setEmail($data['email'])
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setRoles(['ROLE_USER']);

        // Detect language from X-Locale header or Accept-Language
        $locale = $request->headers->get('X-Locale');
        if (!$locale || !in_array($locale, ['fr', 'en'], true)) {
            $locale = $request->getPreferredLanguage(['fr', 'en']) ?? 'fr';
        }
        $user->setLanguage($locale);

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
                'language' => $user->getLanguage(),
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

    #[Route('/connect/google/mobile', name: 'connect_google_mobile_start')]
    public function connectGoogleMobile(Request $request): Response
    {
        $isAndroid = $request->headers->get('X-Platform') === 'android';
        $clientId = $isAndroid ? $this->googleAndroidClientId : $this->googleIosClientId;
        $redirectUri = $isAndroid ? $this->googleAndroidRedirectUri : $this->googleIosRedirectUri;

        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return new JsonResponse([
            'authUrl' => $authUrl,
            'redirectUri' => $redirectUri,
            'codeVerifier' => $codeVerifier,
        ]);
    }

    #[Route('/connect/google/mobile-check', name: 'connect_google_mobile_check')]
    public function connectGoogleMobileCheck(
        Request $request,
        HttpClientInterface $httpClient,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $isAndroid = $request->headers->get('X-Platform') === 'android';
        $clientId = $isAndroid ? $this->googleAndroidClientId : $this->googleIosClientId;
        $redirectUri = $isAndroid ? $this->googleAndroidRedirectUri : $this->googleIosRedirectUri;

        try {
            $tokenResponse = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $request->query->get('code'),
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                    'code_verifier' => $request->query->get('codeVerifier'),
                ],
            ]);

            $tokenData = $tokenResponse->toArray();
            $accessToken = $tokenData['access_token'];

            $userInfoResponse = $httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $googleUser = $userInfoResponse->toArray();

            $email = $googleUser['email'];
            $googleId = $googleUser['sub'];

            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstname($googleUser['given_name'] ?? '');
                $user->setLastname($googleUser['family_name'] ?? '');
                $user->setGoogleId($googleId);
                $user->setRoles(['ROLE_USER']);

                $locale = $request->headers->get('X-Locale');
                if (!$locale || !in_array($locale, ['fr', 'en'], true)) {
                    $locale = $request->getPreferredLanguage(['fr', 'en']) ?? 'fr';
                }
                $user->setLanguage($locale);

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
                    'biography' => $user->getBiography(),
                    'language' => $user->getLanguage(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $this->translator->trans('auth.google.error'),
            ], Response::HTTP_BAD_REQUEST);
        }
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

                $locale = $request->headers->get('X-Locale');
                if (!$locale || !in_array($locale, ['fr', 'en'], true)) {
                    $locale = $request->getPreferredLanguage(['fr', 'en']) ?? 'fr';
                }
                $user->setLanguage($locale);

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
                    'biography' => $user->getBiography(),
                    'language' => $user->getLanguage(),
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception) {
            return new JsonResponse([
                'message' => $this->translator->trans('auth.google.error'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
