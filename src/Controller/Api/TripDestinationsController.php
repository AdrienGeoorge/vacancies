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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/trip-destinations', name: 'api_trip_destinations_')]
class TripDestinationsController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry    $managerRegistry,
        private readonly TripService        $tripService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    #[Route('/delete/{destination}', name: 'delete', requirements: ['destination' => '\d+'], methods: ['DELETE'])]
    public function delete(TripDestination $destination): JsonResponse
    {
        try {
            $this->managerRegistry->getManager()->remove($destination);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('destination.deleted')]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('destination.delete.error')], Response::HTTP_BAD_REQUEST);
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
                    return $this->json(['message' => $this->translator->trans('destination.date.invalid_departure')], Response::HTTP_BAD_REQUEST);
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
                    return $this->json(['message' => $this->translator->trans('destination.date.invalid_return')], Response::HTTP_BAD_REQUEST);
                }
            }

            if ($destination->getDepartureDate() && $destination->getReturnDate()) {
                $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($destination->getTrip(), $destination->getDepartureDate(), $destination->getReturnDate());
                if ($errorOnCompare !== null) {
                    return $this->json(['message' => $this->translator->trans('destination.date.return_before_departure')], Response::HTTP_BAD_REQUEST);
                }

            }

            $this->managerRegistry->getManager()->persist($destination);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('destination.updated')]);
        } catch (\Exception) {
            return $this->json([
                'message' => $this->translator->trans('destination.update.error'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/reorder/{trip}', name: 'reorder', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'trip.access.edit_denied', statusCode: 403)]
    public function reorder(Request $request, Trip $trip): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['order']) || !is_array($data['order'])) {
                return $this->json(['message' => $this->translator->trans('destination.order.invalid_param')], Response::HTTP_BAD_REQUEST);
            }

            $destinations = $trip->getDestinations();

            $destinationsById = [];
            foreach ($destinations as $destination) {
                $destinationsById[$destination->getId()] = $destination;
            }

            foreach ($data['order'] as $index => $destinationId) {
                if (!isset($destinationsById[$destinationId])) {
                    return $this->json(['message' => $this->translator->trans('destination.order.not_in_trip', ['%id%' => $destinationId])], Response::HTTP_BAD_REQUEST);
                }

                $destinationsById[$destinationId]->setDisplayOrder($index);
                $this->managerRegistry->getManager()->persist($destinationsById[$destinationId]);
            }

            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('destination.order.updated')]);
        } catch (\Exception) {
            return $this->json([
                'message' => $this->translator->trans('destination.order.error'),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
