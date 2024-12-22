<?php

namespace App\Controller;

use App\Entity\Accommodation;
use App\Entity\Trip;
use App\Form\AccommodationType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/accommodations', name: 'trip_accommodations_', requirements: ['trip' => '\d+'])]
class AccommodationController extends AbstractController
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
        return $this->render('accommodations/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{accommodation}', name: 'edit', requirements: ['accommodation' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?Accommodation $accommodation): Response
    {
        if (!$accommodation) {
            $accommodation = new Accommodation();
            $accommodation->setTrip($trip);
        }

        if ($accommodation->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce logement n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(AccommodationType::class, $accommodation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $accommodation->getArrivalDate(), $accommodation->getDepartureDate());

                if ($errorOnCompare === null) {
                    $this->managerRegistry->getManager()->persist($accommodation);
                    $this->managerRegistry->getManager()->flush();

                    if ($request->get('_route') === 'trip_accommodations_edit') {
                        $this->addFlash('success', 'Les détails de votre logement ont bien été modifiés.');
                    } else {
                        $this->addFlash('success', 'Ce logement a bien été rattaché à votre voyage.');
                    }

                    return $this->redirectToRoute('trip_accommodations_index', ['trip' => $trip->getId()]);
                } else {
                    $this->addFlash('warning', $errorOnCompare);
                }
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement du logement à votre voyage.');
            }
        }

        return $this->render('accommodations/form.html.twig', [
            'trip' => $trip,
            'accommodation' => $accommodation,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{accommodation}', name: 'delete', requirements: ['accommodation' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, Accommodation $accommodation): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        $this->managerRegistry->getManager()->remove($accommodation);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre logement a bien été dissocié de ce voyage et supprimé.');

        return $this->redirectToRoute('trip_accommodations_index', ['trip' => $trip->getId()]);
    }
}