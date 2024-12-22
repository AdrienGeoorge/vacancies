<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\VariousExpensive;
use App\Form\VariousExpensiveType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/various-expensive', name: 'trip_various_expensive_', requirements: ['trip' => '\d+'])]
class VariousExpensiveController extends AbstractController
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
    public function variousExpensive(Trip $trip): Response
    {
        return $this->render('various-expensive/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{expensive}', name: 'edit', requirements: ['expensive' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?VariousExpensive $expensive): Response
    {
        if (!$expensive) {
            $expensive = new VariousExpensive();
            $expensive->setTrip($trip);
        }

        if ($expensive->getTrip() !== $trip) {
            $this->addFlash('error', 'Cette dépense n\'est pas associée à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(VariousExpensiveType::class, $expensive);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->managerRegistry->getManager()->persist($expensive);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'trip_various_expensive_edit') {
                    $this->addFlash('success', 'Les détails de votre dépense ont bien été modifiés.');
                } else {
                    $this->addFlash('success', 'Cette dépense supplémentaire a bien été rattachée à votre voyage.');
                }

                return $this->redirectToRoute('trip_various_expensive_index', ['trip' => $trip->getId()]);
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement de la dépense à votre voyage.');
            }
        }

        return $this->render('various-expensive/form.html.twig', [
            'trip' => $trip,
            'expensive' => $expensive,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{expensive}', name: 'delete', requirements: ['expensive' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, VariousExpensive $expensive): Response
    {
        $this->managerRegistry->getManager()->remove($expensive);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre dépense supplémentaire a bien été dissociée de ce voyage et supprimée.');

        return $this->redirectToRoute('trip_various_expensive_index', ['trip' => $trip->getId()]);
    }
}