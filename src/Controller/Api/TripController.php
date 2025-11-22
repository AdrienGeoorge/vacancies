<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/trips')]
class TripController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/future/user/{user}', name: 'api_future_trip_by_user')]
    public function future(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', 403]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/passed/user/{user}', name: 'api_passed_trip_by_user')]
    public function passed(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', 403]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser());

        return $this->json($trips);
    }
}
