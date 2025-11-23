<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/trips', name: 'api_trip_')]
class TripController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/future/user/{user}', name: 'future_trip_by_user', methods: ['GET'])]
    public function future(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', 403]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/passed/user/{user}', name: 'passed_trip_by_user', methods: ['GET'])]
    public function passed(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', 403]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser());

        return $this->json($trips);
    }
    #[Route('/all/user/{user}', name: 'all_trip_by_user', methods: ['GET'])]
    public function all(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', 403]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getAllTrips($this->getUser());

        return $this->json($trips);
    }
}
