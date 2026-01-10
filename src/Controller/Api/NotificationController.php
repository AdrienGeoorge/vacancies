<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\UserNotifications;
use App\Service\TimeAgoService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly TimeAgoService  $timeAgoService,
    )
    {
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['message' => 'Utilisateur hors ligne.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->managerRegistry->getRepository(User::class)->find($this->getUser()->getId());

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($user->getUserNotifications()
            ->map(function (UserNotifications $notification) {
                return [
                    'id' => $notification->getId(),
                    'text' => $notification->getText(),
                    'notifiedBy' => $notification->getNotifiedBy(),
                    'receivedAt' => $this->timeAgoService->get($notification->getReceivedAt()),
                    'link' => $notification->getLink(),
                ];
            })->toArray(), Response::HTTP_OK);
    }

    #[Route('/read-all', name: 'read_all', methods: ['POST'])]
    public function readAll(Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['message' => 'Utilisateur hors ligne.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->managerRegistry->getRepository(User::class)->find($this->getUser()->getId());

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['notifications'])) {
            foreach ($user->getUserNotifications() as $notification) {
                if (in_array($notification->getId(), $data['notifications'])) {
                    $user->removeUserNotification($notification);
                    $this->managerRegistry->getManager()->remove($notification);
                }
            }

            $this->managerRegistry->getManager()->flush();
        }

        return $this->json([], Response::HTTP_OK);
    }
}
