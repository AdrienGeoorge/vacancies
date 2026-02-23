<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\TripDocumentRequestDTO;
use App\Entity\Trip;
use App\Entity\TripDocument;
use App\Service\DTOService;
use App\Service\FileUploaderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/trip-documents/{trip}', name: 'api_trip_documents_', requirements: ['trip' => '\\d+'])]
class TripDocumentController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly DTOService      $dtoService,
        private readonly FileUploaderService $uploaderService
    )
    {
    }

    #[Route('/show/{document}', name: 'show', requirements: ['document' => '\\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas accéder aux documents de ce voyage.', statusCode: 403)]
    public function showOrDownload(?Trip $trip = null, ?TripDocument $document = null): Response
    {
        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        if ($document->getTrip() !== $trip) {
            return $this->json(['message' => 'Ce document n\'est pas associé à ce voyage.'], Response::HTTP_FORBIDDEN);
        }

        $filePath = $document->getFile();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($filePath)) {
            return $this->json(['message' => 'Le fichier n\'a pas été trouvé.'], Response::HTTP_NOT_FOUND);
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
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition, Content-Type, Content-Length');

        return $response;
    }

    #[Route('/delete/{document}', name: 'delete', requirements: ['document' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripDocument $document): Response
    {
        if (!$document) {
            return $this->json(['message' => 'Document non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        if ($document->getTrip() !== $trip) {
            return $this->json(['message' => 'Ce document n\'est pas associé à ce voyage.'], Response::HTTP_FORBIDDEN);
        }

        $filePath = $document->getFile();
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($filePath)) {
            return $this->json(['message' => 'Le fichier n\'a pas été trouvé.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $fileSystem->remove($document->getFile());

            $this->managerRegistry->getManager()->remove($document);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Le document a bien été supprimé.']);
        } catch (\Exception $exception) {
            return $this->json(['message' => 'La suppression du document a échoué.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function create(
        Request      $request,
        ValidatorInterface $validator,
        ?Trip        $trip = null,
    ): JsonResponse
    {
        $document = new TripDocument();

        $dto = new TripDocumentRequestDTO();
        $dto->name = $request->request->get('name');
        $dto->file = $request->files->get('file');

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            if ($dto->file) {
                $directory = $this->getParameter('bag_directory') . '/' . $trip->getId();
                $fileName = $this->uploaderService->upload($dto->file, null, $directory);

                $document->setFile($directory . '/' . $fileName);
            }

            $document->setName($dto->name);
            $document->setTrip($trip);

            $this->managerRegistry->getManager()->persist($document);
            $this->managerRegistry->getManager()->flush();

            return $this->json([
                'message' => 'Le document a bien été lié à votre voyage.',
                'files' => $trip->getDocuments()->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Une erreur est survenue lors de l\'ajout du document.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
