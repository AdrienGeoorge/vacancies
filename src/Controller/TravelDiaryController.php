<?php

namespace App\Controller;

use App\Repository\TripRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/travel-diary', name: 'travel_diary_')]
class TravelDiaryController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/board', name: 'board')]
    public function board(): Response
    {
        return $this->render('travel-diary/board.html.twig');
    }

    #[Route('/visited-countries', name: 'visited_countries', options: ['expose' => true])]
    public function visitedCountries(TripRepository $tripRepository): JsonResponse
    {
        $user = $this->getUser();
        $trips = $tripRepository->findBy(['traveler' => $user]);

        $visited = [];
        $upcoming = [];

        foreach ($trips as $trip) {
            $code = $trip->getCountryCode();
            if (!$code) continue;

            if (!$trip->getReturnDate() || $trip->getReturnDate() > new \DateTime()) {
                $upcoming[] = $code;
            } else {
                $visited[] = $code;
            }
        }

        return $this->json([
            'visited' => array_unique($visited),
            'upcoming' => array_unique($upcoming),
        ]);
    }

}