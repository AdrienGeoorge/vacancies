<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\TokenService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/password', name: 'api_password_')]
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

    #[Route('/claim', name: 'claim', methods: ['POST'])]
    public function claim(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['message' => 'Vous devez renseigner une adresse email valide.'], 400);
        }

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return new JsonResponse(['message' => "L'email que vous avez saisie n'est rattachée à aucun compte."], 409);
        }

        try {
            $this->tokenService->create($user);
            return new JsonResponse(['message' => "Nous venons de t'envoyer un lien de confirmation par mail. Tu n'as rien reçu ? Vérifies tes spams et actualises ta messagerie."]);
        } catch (\Exception) {
            return new JsonResponse(['message' => "L'envoi du mail de réinitialisation a échoué. Veuillez réessayer."], 500);
        }
    }

    #[Route('/verify-token', name: 'verify_token', methods: ['POST'])]
    public function verifyToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token'])) {
            return new JsonResponse(['message' => 'Réinitialisation impossible : token non transmis.'], 403);
        }

        $resetToken = $this->tokenService->getUserByToken($data['token']);

        if (!$resetToken || ($resetToken->getTimestamp() < time())) {
            return new JsonResponse(['message' => 'Ce lien a expiré. Merci de bien vouloir réitérer votre demande de changement de mot de passe.'], 403);
        }

        return new JsonResponse([]);
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $resetToken = $this->tokenService->getUserByToken($data['token']);

        if (!$resetToken || ($resetToken->getTimestamp() < time())) {
            return new JsonResponse(['message' => 'Ce lien a expiré. Merci de bien vouloir réitérer votre demande de changement de mot de passe.'], 403);
        }

        if (!isset($data['password'])) {
            return new JsonResponse(['message' => 'Vous devez saisir un nouveau mot de passe.'], 400);
        }

        $resetToken->getUser()->setPassword(
            $userPasswordHasher->hashPassword(
                $resetToken->getUser(),
                $data['password']
            )
        );

        $this->managerRegistry->getManager()->persist($resetToken->getUser());
        $this->managerRegistry->getManager()->remove($resetToken);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse(['message' => 'Votre mot de passe a été modifié avec succès. Vous pouvez désormais vous reconnecter !']);
    }
}
