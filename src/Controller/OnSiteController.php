<?php

namespace App\Controller;

use App\Entity\Accommodation;
use App\Entity\OnSiteExpense;
use App\Entity\Trip;
use App\Form\AccommodationType;
use App\Form\OnSiteExpenseType;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/on-site', name: 'trip_on_site_', requirements: ['trip' => '\d+'])]
class OnSiteController extends AbstractController
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
        return $this->render('on_site/index.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/new', name: 'new')]
    #[Route('/edit/{expense}', name: 'edit', requirements: ['expense' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function form(Request $request, Trip $trip, ?OnSiteExpense $expense): Response
    {
        if (!$expense) {
            $expense = new OnSiteExpense();
            $expense->setTrip($trip);
        }

        if ($expense->getTrip() !== $trip) {
            $this->addFlash('error', 'Cette dépense n\'est pas associée à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(OnSiteExpenseType::class, $expense, ['trip' => $trip]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $expense->getPurchaseDate());

                if ($errorOnCompare === null) {
                    $this->managerRegistry->getManager()->persist($expense);
                    $this->managerRegistry->getManager()->flush();

                    if ($request->get('_route') === 'trip_on_site_edit') {
                        $this->addFlash('success', 'Les détails de cette dépense ont bien été modifiés.');
                    } else {
                        $this->addFlash('success', 'Cette dépense a bien été rattachée à votre voyage.');
                    }

                    return $this->redirectToRoute('trip_on_site_index', ['trip' => $trip->getId()]);
                } else {
                    $this->addFlash('warning', $errorOnCompare);
                }
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors du rattachement de cette dépense à votre voyage.');
            }
        }

        return $this->render('on_site/form.html.twig', [
            'trip' => $trip,
            'expense' => $expense,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{expense}', name: 'delete', requirements: ['expense' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, OnSiteExpense $expense): Response
    {
        $this->managerRegistry->getManager()->remove($expense);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre dépense a bien été dissociée de ce voyage et supprimée.');

        return $this->redirectToRoute('trip_on_site_index', ['trip' => $trip->getId()]);
    }
}