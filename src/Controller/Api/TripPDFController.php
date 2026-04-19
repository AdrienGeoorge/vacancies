<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/trips')]
class TripPDFController extends AbstractController
{
    public function __construct(
        private readonly PdfService          $pdfService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    /**
     * @throws \Exception
     */
    #[Route('/{trip}/export/pdf', name: 'trip_export_pdf', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function exportPDF(?Trip $trip): Response
    {
        if (!$trip) {
            return $this->json(['message' => $this->translator->trans('trip.not_found')], Response::HTTP_NOT_FOUND);
        }

        $formattedData = $this->pdfService->formatTripDataForPDF($trip);

        try {
            $pdfContent = $this->pdfService->generatePDF($formattedData);
            $filename = $this->pdfService->sanitizeFilename($trip->getName()) . '_' . time() . '.pdf';

            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;

        } catch (\Exception $e) {
            return $this->json([
                'error' => $this->translator->trans('trip.pdf.error'),
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
