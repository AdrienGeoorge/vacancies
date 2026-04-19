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
            return new JsonResponse(['message' => $this->translator->trans('password.claim.email_required')], 400);
        }

        $user = $this->managerRegistry->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return new JsonResponse(['message' => $this->translator->trans('password.claim.email_not_found')], 409);
        }

        try {
            $this->tokenService->create($user);
            return new JsonResponse(['message' => $this->translator->trans('password.claim.sent')]);
        } catch (\Exception) {
            return new JsonResponse(['message' => $this->translator->trans('password.claim.send_error')], 500);
        }
    }

    #[Route('/verify-token', name: 'verify_token', methods: ['POST'])]
    public function verifyToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['token'])) {
            return new JsonResponse(['message' => $this->translator->trans('password.reset.token_required')], 403);
        }

        $resetToken = $this->tokenService->getUserByToken($data['token']);

        if (!$resetToken || ($resetToken->getTimestamp() < time())) {
            return new JsonResponse(['message' => $this->translator->trans('password.reset.token_expired')], 403);
        }

        return new JsonResponse([]);
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $resetToken = $this->tokenService->getUserByToken($data['token']);

        if (!$resetToken || ($resetToken->getTimestamp() < time())) {
            return new JsonResponse(['message' => $this->translator->trans('password.reset.token_expired')], 403);
        }

        if (!isset($data['password'])) {
            return new JsonResponse(['message' => $this->translator->trans('password.reset.password_required')], 400);
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

        return new JsonResponse(['message' => $this->translator->trans('password.reset.success')]);
    }
}
