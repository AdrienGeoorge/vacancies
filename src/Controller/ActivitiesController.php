<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\EventType;
use App\Entity\PlanningEvent;
use App\Entity\Trip;
use App\Form\ActivityType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/activities', name: 'trip_activities_', requirements: ['trip' => '\d+'])]
class ActivitiesController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
    }

    #[Route('/', name: 'index')]
    #[IsGranted('view', subject: 'trip')]
    public function activities(Trip $trip): Response
    {
        return $this->render('activities/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{activity}', name: 'edit', requirements: ['activity' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?Activity $activity): Response
    {
        if (!$activity) {
            $activity = new Activity();
            $activity->setTrip($trip);
        }

        if ($activity->getTrip() !== $trip) {
            $this->addFlash('error', 'Cette activité n\'est pas associée à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ActivityType::class, $activity, ['trip' => $trip]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $activity->getDate());

                if ($errorOnCompare === null) {
                    if ($activity->isBooked() && !$activity->getPayedBy()) {
                        $this->addFlash('warning', 'Vous avez indiqué que la réservation a été effectuée mais n\'avez pas renseigné qui a payé.');
                    } else {
                        $newEvent = false;
                        $this->managerRegistry->getManager()->persist($activity);

                        if ($activity->getDate()) {
                            $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);

                            if (!$event) {
                                $eventType = $this->managerRegistry->getRepository(EventType::class)->findOneBy(['name' => 'Autre']);
                                $event = (new PlanningEvent())
                                    ->setTrip($trip)
                                    ->setActivity($activity)
                                    ->setType($eventType);
                                $newEvent = true;
                            }

                            $event->setTitle($activity->getName());
                            $event->setStart($activity->getDate());

                            $this->managerRegistry->getManager()->persist($event);
                        }

                        $this->managerRegistry->getManager()->flush();

                        if ($request->get('_route') === 'trip_activities_edit') {
                            $this->addFlash('success', 'Les détails de votre activité et l\'évènement associé ont bien été modifiés.');
                        } else {
                            $this->addFlash('success', 'Cette activité a bien été rattachée à votre voyage.');
                            if ($newEvent) $this->addFlash('success', 'Un évènement a été ajouté à votre planning pour cette activité.');
                        }

                        return $this->redirectToRoute('trip_activities_index', ['trip' => $trip->getId()]);
                    }
                } else {
                    $this->addFlash('warning', $errorOnCompare);
                }
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement de l\'activité à votre voyage.');
            }
        }

        return $this->render('activities/form.html.twig', [
            'trip' => $trip,
            'activity' => $activity,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{activity}', name: 'delete', requirements: ['activity' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, Activity $activity): Response
    {
        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);
        if ($event) $this->managerRegistry->getManager()->remove($event);

        $this->managerRegistry->getManager()->remove($activity);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre activité a bien été dissociée de ce voyage et supprimée.');

        return $this->redirectToRoute('trip_activities_index', ['trip' => $trip->getId()]);
    }
}