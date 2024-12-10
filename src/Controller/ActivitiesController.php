<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Trip;
use App\Form\ActivityType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip/show/{trip}/activities', name: 'trip_activities_')]
class ActivitiesController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
    }

    #[Route('/', name: 'index', requirements: ['id' => '\d+'])]
    public function activities(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('activities/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{activity}', name: 'edit')]
    public function form(Request $request, Trip $trip, ?Activity $activity): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        if (!$activity) {
            $activity = new Activity();
            $activity->setTrip($trip);
        }

        if ($activity->getTrip() !== $trip) {
            $this->addFlash('error', 'Cette activité n\'est pas associée à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->managerRegistry->getManager()->persist($activity);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'trip_activities_edit') {
                    $this->addFlash('success', 'Les détails de votre activité ont bien été modifiés.');
                } else {
                    $this->addFlash('success', 'Cette activité a bien été rattachée à votre voyage.');
                }

                return $this->redirectToRoute('trip_activities_index', ['trip' => $trip->getId()]);
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

    #[Route('/delete/{activity}', name: 'delete')]
    public function delete(Trip $trip, Activity $activity): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        $this->managerRegistry->getManager()->remove($activity);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre activité a bien été dissociée de ce voyage et supprimée.');

        return $this->redirectToRoute('trip_activities_index', ['trip' => $trip->getId()]);
    }
}