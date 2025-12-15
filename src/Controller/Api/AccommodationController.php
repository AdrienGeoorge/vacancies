<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/accommodations/{trip}', name: 'api_accommodations_', requirements: ['trip' => '\d+'])]
class AccommodationController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAllAccommodation(?Trip $trip = null): Response
    {
        return $this->json($trip->getAccommodations());
    }
}