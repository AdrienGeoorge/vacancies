<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Service\TripService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/plannings/{trip}', name: 'api_planning_', requirements: ['trip' => '\d+'])]
class PlanningController extends AbstractController
{
    public function __construct(private readonly TripService $tripService)
    {
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getPlanning(Trip $trip): Response
    {
        return new JsonResponse($this->tripService->getPlanning($trip));
    }
}