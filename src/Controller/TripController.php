<?php

namespace App\Controller;

use App\Entity\Accommodation;
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
    #[Route('/edit/{trip}', name: 'edit', requirements: ['trip' => '\d+'])]
    public function new(Request $request, ?Trip $trip): Response
    {
        if (!$trip) $trip = new Trip();

        if ($trip->getTraveler() !== null && $trip->getTraveler() !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $image = $form->get('image')->getData();
                    if ($image) {
                        $imageFileName = $this->uploaderService->upload($image);
                        $trip->setImage('/' . $this->getParameter('upload_directory') . '/' . $imageFileName);
                    }

                    if ($trip->getTravelers() <= 0) {
                        $this->addFlash('warning', 'Vous devez saisir un nombre de voyageurs supérieur à 0.');
                    } else {
                        $trip->setTraveler($this->getUser());

                        $this->managerRegistry->getManager()->persist($trip);
                        $this->managerRegistry->getManager()->flush();

                        if ($request->get('_route') === 'trip_edit') {
                            $this->addFlash('success', 'Les informations de ton voyage ont bien été modifiées.');
                        } else {
                            $this->addFlash('success', 'Ton voyage a bien été créé.');
                        }

                        return $this->redirectToRoute('trip_show', ['trip' => $trip->getId()]);
                    }
                } catch (\Exception $exception) {
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du voyage.');
                }
            } else {
                $this->addFlash('error', $form->getErrors(true)->current()->getMessage());
            }
        }

        return $this->render('trip/form.html.twig', [
            'form' => $form->createView(),
            'trip' => $trip
        ]);
    }

    #[Route('/show/{trip}', name: 'show', requirements: ['trip' => '\d+'])]
    public function show(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
            'budget' => $this->tripService->getBudget($trip),
        ]);
    }

    #[Route('/delete/{trip}', name: 'delete', requirements: ['trip' => '\d+'])]
    public function delete(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return $this->redirectToRoute('app_home');

        $this->managerRegistry->getManager()->remove($trip);
        $this->managerRegistry->getManager()->flush();

        $this->addFlash('success', 'Votre voyage a bien été supprimé.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/get-budget/{trip}', name: 'get_budget', requirements: ['trip' => '\d+'], options: ['expose' => true])]
    public function getBudget(Trip $trip): Response
    {
        if ($trip->getTraveler() !== $this->getUser()) return new JsonResponse([], 500);

        return new JsonResponse($this->tripService->getBudget($trip));
    }
}