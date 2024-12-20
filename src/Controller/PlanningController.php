<?php

namespace App\Controller;

use App\Entity\PlanningEvent;
use App\Entity\Trip;
use App\Form\PlanningEventType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip/show/{trip}/planning', name: 'trip_planning_', requirements: ['trip' => '\d+'])]
class PlanningController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
    }

    #[Route('/', name: 'index', options: ['expose' => true])]
    public function planning(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('planning/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{event}', name: 'edit', requirements: ['event' => '\d+'], options: ['expose' => true])]
    public function form(Request $request, Trip $trip, ?PlanningEvent $event): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        if (!$event) {
            $event = new PlanningEvent();
            $event->setTrip($trip);
        }

        if ($event->getTrip() !== $trip) {
            $this->addFlash('error', 'Cet évènement n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(PlanningEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $event->getStart(), $event->getEnd());

                if ($errorOnCompare === null) {
                    $this->managerRegistry->getManager()->persist($event);
                    $this->managerRegistry->getManager()->flush();

                    if ($request->get('_route') === 'trip_planning_edit') {
                        $this->addFlash('success', 'Les détails de votre évènement ont bien été modifiés.');
                    } else {
                        $this->addFlash('success', 'Cet évènement a bien été ajouté au planning de votre voyage.');
                    }

                    return $this->redirectToRoute('trip_planning_index', ['trip' => $trip->getId()]);
                } else {
                    $this->addFlash('warning', $errorOnCompare);
                }
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout de l\'évènement à votre planning.');
            }
        }

        return $this->render('planning/form.html.twig', [
            'trip' => $trip,
            'event' => $event,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{event}', name: 'delete', requirements: ['event' => '\d+'])]
    public function delete(Trip $trip, PlanningEvent $event): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        $this->managerRegistry->getManager()->remove($event);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre évènement a bien été supprimé du planning.');

        return $this->redirectToRoute('trip_planning_index', ['trip' => $trip->getId()]);
    }

    #[Route('/get', name: 'get', options: ['expose' => true])]
    public function getPlanning(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return new JsonResponse([], 500);

        return new JsonResponse($this->tripService->getPlanning($trip));
    }

    #[Route('/drop-event', name: 'drop_event', options: ['expose' => true])]
    public function dropEvent(Request $request, Trip $trip): Response
    {
        if (!$request->isXmlHttpRequest()) return new JsonResponse([], 500);
        if ($trip->getTraveler() !== $this->getUser()) return new JsonResponse([], 500);

        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->find($request->request->get('id'));

        if (!$event) return new JsonResponse([], 500);

        try {
            $event->setStart(new \DateTime($request->request->get('start')));
            $this->managerRegistry->getManager()->persist($event);
            $this->managerRegistry->getManager()->flush();
        } catch (\Exception) {
            return new JsonResponse([], 500);
        }

        return new JsonResponse([], 200);
    }
}