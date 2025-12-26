<?php

namespace App\Controller;

use App\Entity\Trip;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip', name: 'trip_')]
class TripController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/update-bloc-notes/{trip}', name: 'update_bloc_notes', requirements: ['trip' => '\d+'], options: ['expose' => true])]
    #[IsGranted('view', 'trip')]
    public function updateBlocNotes(Request $request, Trip $trip): Response
    {
        if (!$request->isXmlHttpRequest()) {
            $this->addFlash('error', 'Une erreur est survenue. Veuillez recommencer.');
            return new JsonResponse([], 500);
        }

        $trip->setBlocNotes($request->request->get('blocNotes'));
        $this->managerRegistry->getManager()->persist($trip);
        $this->managerRegistry->getManager()->flush();

        return new JsonResponse([], 200);
    }
}