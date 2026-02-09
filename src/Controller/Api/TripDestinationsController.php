<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\TripDestination;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trip-destinations', name: 'api_trip_destinations_')]
class TripDestinationsController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly TripService     $tripService
    )
    {
    }

    #[Route('/delete/{destination}', name: 'delete', requirements: ['destination' => '\d+'], methods: ['DELETE'])]
    public function delete(TripDestination $destination): JsonResponse
    {
        try {
            $this->managerRegistry->getManager()->remove($destination);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Destination supprimée du voyage.']);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la suppression de la destination.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/update/{destination}', name: 'update', requirements: ['destination' => '\d+'], methods: ['POST', 'PATCH'])]
    public function update(Request $request, TripDestination $destination): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if ($data['departureDate'] === null || $data['departureDate'] === '') {
                $destination->setDepartureDate(null);
            } else {
                try {
                    $departureDate = new \DateTime($data['departureDate']);
                    $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($destination->getTrip(), $departureDate);

                    if ($errorOnCompare === null) {
                        $destination->setDepartureDate($departureDate);
                    } else {
                        return $this->json(['message' => $errorOnCompare], Response::HTTP_BAD_REQUEST);
                    }
                } catch (\Exception) {
                    return $this->json(['message' => 'La date de départ est invalide.'], Response::HTTP_BAD_REQUEST);
                }
            }

            if ($data['returnDate'] === null || $data['returnDate'] === '') {
                $destination->setReturnDate(null);
            } else {
                try {
                    $returnDate = new \DateTime($data['returnDate']);
                    $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($destination->getTrip(), null, $returnDate);

                    if ($errorOnCompare === null) {
                        $destination->setReturnDate($returnDate);
                    } else {
                        return $this->json(['message' => $errorOnCompare], Response::HTTP_BAD_REQUEST);
                    }
                } catch (\Exception) {
                    return $this->json(['message' => 'La date de retour est invalide.'], Response::HTTP_BAD_REQUEST);
                }
            }

            if ($destination->getDepartureDate() && $destination->getReturnDate()) {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($destination->getTrip(), $destination->getDepartureDate(), $destination->getReturnDate());
                if ($errorOnCompare !== null) {
                    return $this->json(['message' => 'La date de départ ne peut pas être avant la date de retour.'], Response::HTTP_BAD_REQUEST);
                }

            }

            $this->managerRegistry->getManager()->persist($destination);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Destination mise à jour avec succès.']);
        } catch (\Exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de la destination.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/reorder/{trip}', name: 'reorder', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'Vous ne pouvez pas modifier ce voyage.', statusCode: 403)]
    public function reorder(Request $request, Trip $trip): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['order']) || !is_array($data['order'])) {
                return $this->json(['message' => 'Le paramètre "order" est requis et doit être un tableau.'], Response::HTTP_BAD_REQUEST);
            }

            $destinations = $trip->getDestinations();

            $destinationsById = [];
            foreach ($destinations as $destination) {
                $destinationsById[$destination->getId()] = $destination;
            }

            foreach ($data['order'] as $index => $destinationId) {
                if (!isset($destinationsById[$destinationId])) {
                    return $this->json(['message' => "La destination avec l'ID {$destinationId} n'appartient pas à ce voyage."], Response::HTTP_BAD_REQUEST);
                }

                $destinationsById[$destinationId]->setDisplayOrder($index);
                $this->managerRegistry->getManager()->persist($destinationsById[$destinationId]);
            }

            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Ordre des destinations mis à jour avec succès.']);
        } catch (\Exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'ordre.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
