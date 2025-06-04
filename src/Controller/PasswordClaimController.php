<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordClaimType;
use App\Form\PasswordResetType;
use App\Service\TokenService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/password', name: 'password_')]
class PasswordClaimController extends AbstractController
{
    protected ManagerRegistry $managerRegistry;
    protected TranslatorInterface $translator;
    protected TokenService $tokenService;

    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, TokenService $tokenService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
        $this->tokenService = $tokenService;
    }

    #[Route('/claim', name: 'claim')]
    public function claim(Request $request): RedirectResponse|Response
    {
        if ($this->getUser()) return $this->redirectToRoute('app_home');

        $claimForm = $this->createForm(PasswordClaimType::class, []);
        $claimForm->handleRequest($request);

        if ($claimForm->isSubmitted() && $claimForm->isValid()) {
            $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $claimForm->get('email')->getData()]);

            if (!$user) {
                $this->addFlash('error', 'L\'email que vous avez saisie n\'est rattachée à aucun compte.');
            } else {
                try {
                    $this->tokenService->create($user);
                    $this->addFlash('success', 'Nous venons de t\'envoyer un lien de confirmation par mail. Tu n\'as rien reçu? Vérifies tes spams et actualises ta messagerie.');
                } catch (\Exception) {
                    $this->addFlash('error', 'L\'envoi du mail de réinitialisation a échoué. Veuillez réessayer.');
                }
            }
        }

        return $this->render('password-claim/claim.html.twig', [
            'claimForm' => $claimForm->createView()
        ]);
    }

    #[Route('/reset/{token}', name: 'reset', requirements: ['token' => '.+'])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher, string $token): RedirectResponse|Response
    {
        if ($this->getUser()) return $this->redirectToRoute('app_home');

        $resetToken = $this->tokenService->getUserByToken($token);

        if (!$resetToken || ($resetToken->getTimestamp() < time())) {
            $this->addFlash('warning', 'Ce lien a expiré. Merci de bien vouloir réitérer votre demande de changement de mot de passe.');
            return $this->redirectToRoute('auth_login');
        }

        $resetForm = $this->createForm(PasswordResetType::class, []);
        $resetForm->handleRequest($request);

        if ($resetForm->isSubmitted() && $resetForm->isValid()) {
            $resetToken->getUser()->setPassword(
                $userPasswordHasher->hashPassword(
                    $resetToken->getUser(),
                    $resetForm->get('password')->getData()
                )
            );

            $this->managerRegistry->getManager()->persist($resetToken->getUser());
            $this->managerRegistry->getManager()->remove($resetToken);
            $this->managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Vous pouvez désormais vous reconnecter !');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('password-claim/reset.html.twig', [
            'token' => $token,
            'resetForm' => $resetForm->createView()
        ]);
    }
}
