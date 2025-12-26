<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\TripDocument;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trip-documents/{trip}', name: 'api_trip_documents_', requirements: ['trip' => '\\d+'])]
class TripDocumentController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route('/show/{document}', name: 'show', requirements: ['document' => '\\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas accéder aux documents de ce voyage.', statusCode: 403)]
    public function showOrDownload(?Trip $trip = null, ?TripDocument $document = null): Response
    {
        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        if ($document->getTrip() !== $trip) {
            return $this->json(['message' => 'Ce document n\'est pas associé à ce voyage.'], 403);
        }

        $filePath = $document->getFile();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($filePath)) {
            return $this->json(['message' => 'Le fichier n\'a pas été trouvé.'], 404);
        }

        $mimeTypes = new MimeTypes();
        $typeMime = $mimeTypes->guessMimeType($filePath) ?? 'application/octet-stream';

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $typeMime);

        // Affichage direct pour images et PDF, téléchargement pour le reste
        $disposition = str_starts_with($typeMime, 'image/') || str_starts_with($typeMime, 'application/pdf')
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;

        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        $response->setContentDisposition($disposition, $fileName);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        // Expose headers so that a frontend (e.g., Vue) can read them via fetch/axios across origins
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition, Content-Type, Content-Length');

        return $response;
    }

    #[Route('/delete/{document}', name: 'delete', requirements: ['document' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripDocument $document): Response
    {
        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], 404);
        }

        if ($document->getTrip() !== $trip) {
            return $this->json(['message' => 'Ce document n\'est pas associé à ce voyage.'], 403);
        }

        $filePath = $document->getFile();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($filePath)) {
            return $this->json(['message' => 'Le fichier n\'a pas été trouvé.'], 404);
        }

        try {
            $fileSystem->remove($document->getFile());

            $this->managerRegistry->getManager()->remove($document);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Le document a bien été supprimé.']);
        } catch (\Exception $exception) {
            return $this->json(['message' => 'La suppression du document a échoué.'], 500);
        }
    }
}
