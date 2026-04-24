<?php

namespace App\Service;

use App\Entity\Trip;
use App\Entity\TripTraveler;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PdfService
{
    public function __construct(
        private readonly Environment         $twig,
        private readonly TripService         $tripService,
        private readonly TranslatorInterface $translator,
        private readonly string              $domain,
        private readonly string              $apiUrl,
        private readonly string              $projectDir,
        private readonly string              $appName,
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
            'destinationTitle' => $this->tripService->formateDestinationsForString($this->tripService->getDestinations($trip)),
            'destinations' => $this->tripService->getDestinations($trip, true),
            'departureDate' => $trip->getDepartureDate(),
            'returnDate' => $trip->getReturnDate(),
            'duration' => $trip->getDepartureDate()->diff($trip->getReturnDate())->days + 1,
            'description' => $trip->getDescription(),
            'coverImage' => $trip->getImage() ?: null,
            'currency' => $trip->getCurrency()?->getSymbol(),

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

    private function imageToBase64(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        $data = file_get_contents($filePath);
        $mime = mime_content_type($filePath) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function generatePDF(array $trip): string
    {
        $logoSrc = $this->imageToBase64($this->projectDir . '/public/images/logo.png')
            ?? ($this->domain . '/images/logo.png');

        $coverImageSrc = null;
        if (!empty($trip['coverImage'])) {
            $localPath = $this->projectDir . '/public/' . ltrim($trip['coverImage'], '/');
            $coverImageSrc = $this->imageToBase64($localPath)
                ?? ($this->apiUrl . $trip['coverImage']);
        }

        $html = $this->twig->render('pdf/trip_report_full.html.twig', [
            'trip' => $trip,
            'logoPath' => $logoSrc,
            'coverImageSrc' => $coverImageSrc,
            'generatedAt' => new \DateTime(),
            'locale' => $this->translator->getLocale(),
            'app_name' => $this->appName,
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
