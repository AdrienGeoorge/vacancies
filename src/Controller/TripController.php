<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Form\TripFormType;
use App\Service\FileUploaderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip', name: 'trip_')]
class TripController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private FileUploaderService $uploaderService;

    public function __construct(ManagerRegistry $managerRegistry, FileUploaderService $uploaderService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->uploaderService = $uploaderService;
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $trip = new Trip();
        $form = $this->createForm(TripFormType::class, $trip);
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
            'trip' => $trip
        ]);
    }
}