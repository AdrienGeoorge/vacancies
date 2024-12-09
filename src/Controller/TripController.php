<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Form\TripType;
use App\Service\FileUploaderService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip', name: 'trip_')]
class TripController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private FileUploaderService $uploaderService;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, FileUploaderService $uploaderService,
                                TripService     $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->uploaderService = $uploaderService;
        $this->tripService = $tripService;
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $trip = new Trip();
        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $headerFile = $form->get('image')->getData();
                $headerFileName = $this->uploaderService->upload($headerFile);
                $trip->setImage('/' . $this->getParameter('upload_directory') . '/' . $headerFileName);
                $trip->setTraveler($this->getUser());

                $this->managerRegistry->getManager()->persist($trip);
                $this->managerRegistry->getManager()->flush();
                $this->addFlash('success', 'Ton voyage a bien été créé.');
                return $this->redirectToRoute('app_home');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du voyage.');
            }
        }

        return $this->render('trip/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/show/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'reservedAccommodationsPrice' => $this->tripService->getReservedAccommodationsPrice($trip),
            'reservedTransportsPrice' => $this->tripService->getReservedTransportsPrice($trip)
        ]);
    }

    #[Route('/get-budget/{id}', name: 'get_budget', requirements: ['id' => '\d+'], options: ['expose' => true])]
    public function getBudget(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return new JsonResponse([], 500);

        return new JsonResponse($this->tripService->getBudget($trip));
    }
}