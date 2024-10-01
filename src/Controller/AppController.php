<?php

namespace App\Controller;

use App\Entity\Trip;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_')]
class AppController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        $futureTrips = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser());
        $passedTrips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser());

        return $this->render('home/index.html.twig', [
            'futureTrips' => $futureTrips,
            'passedTrips' => $passedTrips
        ]);
    }
}
