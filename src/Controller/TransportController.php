<?php

namespace App\Controller;

use App\Entity\Accommodation;
use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\Trip;
use App\Form\AccommodationType;
use App\Form\TransportFormType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip/show/{trip}/transports', name: 'trip_transports_')]
class TransportController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, TripService $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tripService = $tripService;
    }

    #[Route('/', name: 'index', requirements: ['id' => '\d+'])]
    public function transports(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('transports/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{transport}', name: 'edit')]
    public function form(Request $request, Trip $trip, ?Transport $transport): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        if (!$transport) {
            $transport = new Transport();
            $transport->setTrip($trip);
        }

        if ($transport->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce moyen de transport n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(TransportFormType::class, $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->managerRegistry->getManager()->persist($transport);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'trip_transports_edit') {
                    $this->addFlash('success', 'Les détails de votre moyen de transport ont bien été modifiés.');
                } else {
                    $this->addFlash('success', 'Ce moyen de transport a bien été rattaché à votre voyage.');
                }

                return $this->redirectToRoute('trip_transports_index', ['trip' => $trip->getId()]);
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement du moyen de transport à votre voyage.');
            }
        }

        return $this->render('transports/form.html.twig', [
            'trip' => $trip,
            'transport' => $transport,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{transport}', name: 'delete')]
    public function delete(Trip $trip, Transport $transport): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre moyen de transport a bien été dissocié de ce voyage et supprimé.');

        return $this->redirectToRoute('trip_transports_index', ['trip' => $trip->getId()]);
    }
}