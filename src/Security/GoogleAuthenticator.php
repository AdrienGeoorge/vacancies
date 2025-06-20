<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry         $clientRegistry,
        private UserRepository         $userRepository,
        private EntityManagerInterface $em,
        private RouterInterface        $router
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        // L'authenticator ne doit gérer que la route de callback (auth_connect_google_check)
        return $request->attributes->get('_route') === 'auth_connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');

        // Récupère l’access token depuis la requête (code OAuth dans l’URL)
        $accessToken = $client->getAccessToken();

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);

        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();
        $firstname = $googleUser->getFirstName();
        $lastname = $googleUser->getLastName();
        $avatar = $googleUser->getAvatar();

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($avatar, $googleId, $email, $firstname, $lastname) {
                $user = $this->userRepository->findOneBy(['googleId' => $googleId]);

                if (!$user) {
                    $user = $this->userRepository->findOneBy(['email' => $email]);

                    if ($user) {
                        $user->setGoogleId($googleId);
                    } else {
                        $user = new User();
                        $user->setEmail($email);
                        $user->setFirstname($firstname);
                        $user->setLastname($lastname);
                        $user->setAvatar($avatar);
                        $user->setUsername(strtr(utf8_decode(
                            strtolower($firstname . $lastname . substr(bin2hex(random_bytes(3)), 0, 5))
                        ), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));
                        $user->setGoogleId($googleId);
                        $user->setRoles(['ROLE_USER']);
                        $user->setPassword('');
                    }
                }

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        return new RedirectResponse($targetPath ?? $this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->router->generate('auth_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('auth_login'));
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
