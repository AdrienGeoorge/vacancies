<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\TripPhoto;
use App\Repository\TripRepository;
use App\Service\TimeAgoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/story', name: 'api_public_story_', requirements: ['trip' => '\\d+'])]
class StoryController extends AbstractController
{
    public function __construct(
        private readonly TripRepository $tripRepository,
        private readonly TimeAgoService $timeAgoService
    )
    {
    }

    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $trip = $this->tripRepository->findOneBy(['storyToken' => $token]);

        if (!$trip) {
            return $this->json(['message' => 'Story introuvable.'], Response::HTTP_NOT_FOUND);
        }

        date_default_timezone_set('Europe/Paris');

        $storyPhotos = $trip->getPhotos()
            ->filter(fn(TripPhoto $p) => $p->isActiveStory())
            ->map(fn(TripPhoto $p) => [
                'file' => $p->getFile(),
                'title' => $p->getTitle(),
                'caption' => $p->getCaption(),
                'uploadedAt' => $p->getUploadedAt()?->format('Y-m-d H:i:s'),
                'timeAgo' => $this->timeAgoService->get($p->getUploadedAt()),
                'uploadedBy' => $p->getUploadedBy() ? [
                    'firstname' => $p->getUploadedBy()->getCompleteName(),
                ] : null,
            ])
            ->getValues();

        $destinations = $trip->getDestinations()
            ->map(fn($d) => [
                'country' => ['name' => $d->getCountry()->getName()],
                'displayOrder' => $d->getDisplayOrder(),
                'departureDate' => $d->getDepartureDate()?->format('Y-m-d'),
                'returnDate' => $d->getReturnDate()?->format('Y-m-d'),
            ])
            ->getValues();

        $accommodations = $trip->getAccommodations()
            ->map(fn($a) => [
                'name' => $a->getName(),
                'city' => $a->getCity(),
                'country' => $a->getCountry(),
                'arrivalDate' => $a->getArrivalDate()?->format('Y-m-d'),
                'departureDate' => $a->getDepartureDate()?->format('Y-m-d'),
            ])
            ->getValues();

        return $this->json([
            'trip' => [
                'name' => $trip->getName(),
                'departureDate' => $trip->getDepartureDate()?->format('Y-m-d'),
                'returnDate' => $trip->getReturnDate()?->format('Y-m-d'),
            ],
            'destinations' => $destinations,
            'accommodations' => $accommodations,
            'photos' => $storyPhotos,
        ]);
    }
}
