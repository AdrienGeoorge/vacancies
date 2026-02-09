<?php

namespace App\Service;

use App\Entity\Trip;
use App\Entity\TripTraveler;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TripService $tripService,
        private readonly string      $domain
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function formatTripDataForPDF(Trip $trip): array
    {
        return [
            'name' => $trip->getName(),
            'destination' => $this->tripService->formateDestinationsForString($this->tripService->getDestinations($trip)),
            'departureDate' => $trip->getDepartureDate(),
            'returnDate' => $trip->getReturnDate(),
            'duration' => $trip->getDepartureDate()->diff($trip->getReturnDate())->days + 1,
            'description' => $trip->getDescription(),
            'coverImage' => $trip->getImage() ?: null,

            // Travelers
            'travelers' => array_map(function (TripTraveler $traveler) {
                return [
                    'name' => $traveler->getId() ? $traveler->getInvited()->getCompleteName() : $traveler->getName(),
                ];
            }, $trip->getTripTravelers()->toArray()),

            // Budget
            'budget' => $this->tripService->getBudget($trip),

            // Planning
            'planning' => $this->tripService->getEventsByDay($trip),

            // Stats
            'stats' => [
                'days' => $trip->getDepartureDate()->diff($trip->getReturnDate())->days + 1,
                'activities' => $trip->getActivities()->count(),
            ],

            // Notes
            'notes' => $trip->getBlocNotes()
        ];
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function generatePDF(array $trip): string
    {
        $html = $this->twig->render('pdf/trip_report_full.html.twig', [
            'trip' => $trip,
            'logoPath' => $this->domain . '/images/logo.png',
            'generatedAt' => new \DateTime()
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Pour charger les images
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $filename);
        return strtolower($filename);
    }
}
