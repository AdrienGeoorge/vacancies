<?php

namespace App\Controller\Api;

use App\DTO\ActivityRequestDTO;
use App\Entity\Activity;
use App\Entity\EventType;
use App\Entity\PlanningEvent;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\DTOService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/activities/{trip}', name: 'api_activities_', requirements: ['trip' => '\d+'])]
class ActivityController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry $managerRegistry,
        readonly TripService     $tripService,
        readonly DTOService      $dtoService
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        return $this->json(
            $this->managerRegistry->getRepository(Activity::class)->findAllByTrip($trip)
        );
    }

    #[Route('/get/{activity}/form-data', name: 'getFormData', requirements: ['activity' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => 'Edition impossible : activité non trouvée.'], 404);
        }

        return $this->json([
            'type' => $activity->getType(),
            'name' => $activity->getName(),
            'description' => $activity->getDescription(),
            'date' => $activity->getDate(),
            'price' => $activity->getPrice(),
            'perPerson' => $activity->isPerPerson()
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{activity}', name: 'edit', requirements: ['activity' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function create(
        Request   $request,
        ?Trip     $trip = null,
        ?Activity $activity = new Activity()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $selectedType = $data['selectedType'] ? $this->managerRegistry->getRepository(EventType::class)->find($data['selectedType']) : null;

        $dto = new ActivityRequestDTO($selectedType);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $dto->date);

            if ($errorOnCompare === null) {
                $activity->setTrip($trip);
                $activity = $this->dtoService->mapToEntity($dto, $activity);

                $this->managerRegistry->getManager()->persist($activity);

                if ($activity->getDate()) {
                    $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);

                    if (!$event) {
                        $eventType = $this->managerRegistry->getRepository(EventType::class)->findOneBy(['name' => 'Autre']);
                        $event = (new PlanningEvent())
                            ->setTrip($trip)
                            ->setActivity($activity)
                            ->setDescription($activity->getDescription())
                            ->setType($eventType);
                    }

                    $event->setTitle($activity->getName());
                    $event->setStart($activity->getDate());

                    $this->managerRegistry->getManager()->persist($event);
                }

                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'api_activities_edit') {
                    return $this->json(['message' => 'Les informations de ton activité ont bien été modifiées.']);
                }

                return $this->json(['message' => 'Cette activité a bien été ajoutée à votre voyage.']);
            } else {
                return $this->json(['message' => $errorOnCompare], 400);
            }
        } catch (\Exception $e) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de cette activité.'], 400);
        }
    }

    #[Route('/delete/{activity}', name: 'delete', requirements: ['activity' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => 'Suppression impossible : activité non trouvée.'], 404);
        }

        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);
        if ($event) $this->managerRegistry->getManager()->remove($event);

        $this->managerRegistry->getManager()->remove($activity);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Votre activité a bien été dissociée de ce voyage et supprimée.']);
    }

    #[Route('/update-reserved/{activity}', name: 'update_reserved', requirements: ['activity' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => 'Modification impossible : activité non trouvée.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $activity->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $activity->setPayedBy(null);
        }

        $activity->setBooked(!$activity->isBooked());

        $this->managerRegistry->getManager()->persist($activity);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Activité modifiée avec succès.']);
    }
}