<?php

namespace App\Controller\Api;

use App\Entity\PlanningEvent;
use App\Entity\Trip;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/plannings/{trip}', name: 'api_planning_', requirements: ['trip' => '\d+'])]
class PlanningController extends AbstractController
{
    public function __construct(
        private readonly TripService     $tripService,
        private readonly ManagerRegistry $managerRegistry
    )
    {
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getPlanning(Trip $trip): Response
    {
        return new JsonResponse($this->tripService->getPlanning($trip));
    }

    #[Route('/drop-event/{event}', name: 'drop_event', requirements: ['event' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function dropEvent(Request $request, Trip $trip, ?PlanningEvent $event): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$event) {
            return $this->json(['message' => 'Evénement non existant.'], 500);
        }

        try {
            $event->setStart(new \DateTime($data['start']));
            if ($data['end']) $event->setEnd(new \DateTime($data['end']));
            $this->managerRegistry->getManager()->persist($event);
            $this->managerRegistry->getManager()->flush();
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la mise à jour de l\'évènement.'], 500);
        }

        return $this->json([]);
    }
}