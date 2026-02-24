<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TripPhoto;
use App\Repository\TripRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StoryController extends AbstractController
{
    public function __construct(
        private readonly TripRepository $tripRepository,
    ) {
    }

    #[Route('/story/{token}', name: 'public_story', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        $trip = $this->tripRepository->findOneBy(['storyToken' => $token]);

        if (!$trip) {
            return $this->json(['message' => 'Story introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $storyPhotos = $trip->getPhotos()
            ->filter(fn(TripPhoto $p) => $p->isActiveStory())
            ->map(fn(TripPhoto $p) => [
                'file'       => $p->getFile(),
                'caption'    => $p->getCaption(),
                'uploadedAt' => $p->getUploadedAt()?->format('Y-m-d H:i:s'),
                'uploadedBy' => $p->getUploadedBy() ? [
                    'firstname' => $p->getUploadedBy()->getCompleteName(),
                ] : null,
            ])
            ->getValues();

        return $this->json([
            'trip' => [
                'name'          => $trip->getName(),
                'departureDate' => $trip->getDepartureDate()?->format('Y-m-d'),
                'returnDate'    => $trip->getReturnDate()?->format('Y-m-d'),
            ],
            'photos' => $storyPhotos,
        ]);
    }
}
