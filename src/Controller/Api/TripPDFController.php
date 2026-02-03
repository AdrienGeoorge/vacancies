<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\PdfService;
use App\Service\TripService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trips')]
class TripPDFController extends AbstractController
{
    public function __construct(
        private readonly PdfService $pdfGenerator,
        private string              $domain, private readonly TripService $tripService
    )
    {
    }

    #[Route('/{trip}/export/pdf', name: 'trip_export_pdf', requirements: ['trip' => '\d+'], methods: ['GET'])]
//    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function exportPDF(?Trip $trip, Request $request): Response
    {
        if (!$trip) {
            return $this->json(['message' => 'Voyage non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? 'full';

        if (!in_array($type, ['full', 'planning'])) {
            return $this->json(['error' => 'Type d\'export invalide, veuillez utiliser : "full" ou "planning".'], Response::HTTP_BAD_REQUEST);
        }

        $formattedData = $this->formatTripDataForPDF($trip);
        $logoPath = $this->domain . '/images/logo.png';

//        dd($formattedData);
//        return $this->render('pdf/trip_report_full.html.twig', [
//        'trip' => $formattedData,
//        'logoPath' => $logoPath,
//        'generatedAt' => new \DateTime()
//    ]);
        // Generate PDF
        try {
            if ($type === 'full') {
                $pdfContent = $this->pdfGenerator->generateFullReport($formattedData, $logoPath);
            } else {
                $pdfContent = $this->pdfGenerator->generatePlanningOnly($formattedData, $logoPath);
            }
            
            // Prepare filename
            $filename = $this->sanitizeFilename($trip->getName()) . '_' . $type . '_' . time() . '.pdf';
            
            // Return PDF as download
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
            return $response;
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate PDF',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $filename);
        return strtolower($filename);
    }

    private function formatTripDataForPDF(Trip $trip): array
    {
        return [
            'name' => $trip->getName(),
            'destination' => $trip->getCountry()->getName(),
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
            'planning' => $this->tripService->getPlanning($trip),

            // Stats
            'stats' => [
                'days' => $trip->getDepartureDate()->diff($trip->getReturnDate())->days + 1,
                'activities' => $trip->getActivities()->count(),
            ],

            // Notes
            'notes' => $trip->getBlocNotes()
        ];
    }
}
