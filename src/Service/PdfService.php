<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PdfService
{
    private Environment $twig;
    
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function generateFullReport(array $trip, ?string $logoPath = null): string
    {
        $html = $this->twig->render('pdf/trip_report_full.html.twig', [
            'trip' => $trip,
            'logoPath' => $logoPath,
            'generatedAt' => new \DateTime()
        ]);
        
        return $this->generatePDF($html);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function generatePlanningOnly(array $trip, ?string $logoPath = null): string
    {
        $html = $this->twig->render('pdf/trip_planning.html.twig', [
            'trip' => $trip,
            'logoPath' => $logoPath,
            'generatedAt' => new \DateTime()
        ]);
        
        return $this->generatePDF($html);
    }

    private function generatePDF(string $html): string
    {
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
}
