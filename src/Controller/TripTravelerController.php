<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Form\TripTravelerType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/travelers', name: 'trip_traveler_', requirements: ['trip' => '\d+'])]
class TripTravelerController extends AbstractController
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
    public function accommodations(Trip $trip): Response
    {
        return $this->render('trip_travelers/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{traveler}', name: 'edit', requirements: ['traveler' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?TripTraveler $traveler): Response
    {
        if (!$traveler) {
            $traveler = new TripTraveler();
            $traveler->setTrip($trip);
        }

        if ($traveler->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce voyageur ne fait pas parti de ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(TripTravelerType::class, $traveler);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->managerRegistry->getManager()->persist($traveler);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'trip_traveler_edit') {
                    $this->addFlash('success', 'Les détails du voyageur ont bien été modifiés.');
                } else {
                    $this->addFlash('success', 'Ce voyageur a bien été rattaché à votre voyage.');
                }

                return $this->redirectToRoute('trip_traveler_index', ['trip' => $trip->getId()]);
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement de ce voyageur.');
            }
        }

        return $this->render('trip_travelers/form.html.twig', [
            'trip' => $trip,
            'traveler' => $traveler,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{traveler}', name: 'delete', requirements: ['traveler' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripTraveler $traveler): Response
    {
        $this->managerRegistry->getManager()->remove($traveler);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Ce voyageur a bien été dissocié de ce voyage et supprimé.');

        return $this->redirectToRoute('trip_traveler_index', ['trip' => $trip->getId()]);
    }
}