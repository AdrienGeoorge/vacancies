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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/share', name: 'api_public_trip_', requirements: ['trip' => '\\d+'])]
class TripShareController extends AbstractController
{
    public function __construct(
        private readonly TripRepository      $tripRepository,
        private readonly TimeAgoService      $timeAgoService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    #[Route('/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $trip = $this->tripRepository->findOneBy(['shareToken' => $token]);

        if (!$trip) {
            return $this->json(['message' => $this->translator->trans('share.not_found')], Response::HTTP_NOT_FOUND);
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

        $travelers = $trip->getTripTravelers()
            ->map(fn($t) => ['name' => $t->getName()])
            ->getValues();

        return $this->json([
            'trip' => [
                'name' => $trip->getName(),
                'description' => $trip->getDescription(),
                'departureDate' => $trip->getDepartureDate()?->format('Y-m-d'),
                'returnDate' => $trip->getReturnDate()?->format('Y-m-d'),
                'image' => $trip->getImage(),
            ],
            'travelers' => $travelers,
            'destinations' => $destinations,
            'accommodations' => $accommodations,
            'photos' => $storyPhotos,
        ]);
    }
}
