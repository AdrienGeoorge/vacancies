<?php

namespace App\Controller\Api;

use App\DTO\EventRequestDTO;
use App\Entity\EventType;
use App\Entity\PlanningEvent;
use App\Entity\Trip;
use App\Service\DTOService;
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
        private readonly ManagerRegistry $managerRegistry,
        private readonly DTOService      $dtoService
    )
    {
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getPlanning(Trip $trip): Response
    {
        return new JsonResponse($this->tripService->getPlanning($trip));
    }

    #[Route('/delete/{event}', name: 'delete', requirements: ['event' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function delete(Trip $trip, ?PlanningEvent $event): JsonResponse
    {
        if (!$event) {
            return $this->json(['message' => 'Evénement non existant.'], 500);
        }

        $this->managerRegistry->getManager()->remove($event);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Votre évènement a bien été supprimé du planning']);
    }

    #[Route('/drop-event/{event}', name: 'drop_event', requirements: ['event' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
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

    #[Route('/get/{event}/form-data', name: 'getFormData', requirements: ['event' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?PlanningEvent $event = null): JsonResponse
    {
        if (!$event) {
            return $this->json(['message' => 'Edition impossible : évènement non trouvé.'], 404);
        }

        return $this->json([
            'title' => $event->getTitle(),
            'start' => $event->getStart()?->format('Y-m-d H:i'),
            'end' => $event->getEnd()?->format('Y-m-d H:i'),
            'type' => $event->getType(),
            'description' => $event->getDescription(),
            'timeToGo' => $event->getTimeToGo()
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{event}', name: 'edit', requirements: ['event' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function form(Request $request, Trip $trip, ?PlanningEvent $event): Response
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ? $this->managerRegistry->getRepository(EventType::class)->find($data['type']) : null;

        $dto = new EventRequestDTO($type);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates(
                $trip,
                $dto->start ?? null,
                $dto->end ?? null
            );

            if ($errorOnCompare === null) {
                if (!$event) {
                    $event = new PlanningEvent();
                    $event->setTrip($trip);
                }

                $event = $this->dtoService->mapToEntity($dto, $event);

                $this->managerRegistry->getManager()->persist($event);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'api_planning_edit') {
                    return $this->json(['message' => 'Les informations de ton évènement ont bien été modifiées.']);
                }

                return $this->json(['message' => 'Cet évènement a bien été ajouté à votre voyage.']);
            } else {
                return $this->json(['message' => $errorOnCompare], 400);
            }
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de cet évènement.'], 400);
        }
    }
}