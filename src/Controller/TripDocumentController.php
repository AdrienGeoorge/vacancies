<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Entity\TripDocument;
use App\Form\TripDocumentType;
use App\Service\FileUploaderService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trip/show/{trip}/documents', name: 'trip_documents_', requirements: ['trip' => '\d+'])]
class TripDocumentController extends AbstractController
{
    private ManagerRegistry $managerRegistry;
    private FileUploaderService $uploaderService;
    private TripService $tripService;

    public function __construct(ManagerRegistry $managerRegistry, FileUploaderService $uploaderService,
                                TripService     $tripService)
    {
        $this->managerRegistry = $managerRegistry;
        $this->uploaderService = $uploaderService;
        $this->tripService = $tripService;
    }

    #[Route('/new', name: 'new')]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function new(Trip $trip, Request $request): Response
    {
        $document = new TripDocument();
        $form = $this->createForm(TripDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $directory = $this->getParameter('bag_directory') . '/' . $trip->getId();

                    $file = $form->get('file')->getData();
                    $fileName = $this->uploaderService->upload($file, null, $directory);

                    $document->setFile($directory . '/' . $fileName);
                    $document->setTrip($trip);

                    $this->managerRegistry->getManager()->persist($document);
                    $this->managerRegistry->getManager()->flush();

                    $this->addFlash('success', 'Votre document a bien été lié à ce voyage.');

                    return $this->redirectToRoute('trip_documents_bag', ['trip' => $trip->getId()]);
                } catch (\Exception $exception) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement du document.');
                }
            } else {
                $this->addFlash('error', $form->getErrors(true)->current()->getMessage());
            }
        }

        return $this->render('trip_documents/form.html.twig', [
            'form' => $form->createView(),
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/bag', name: 'bag', requirements: ['trip' => '\d+'])]
    #[IsGranted('view', subject: 'trip')]
    public function bag(Trip $trip): Response
    {
        return $this->render('trip_documents/bag.html.twig', [
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip),
        ]);
    }

    #[Route('/delete/{document}', name: 'delete', requirements: ['document' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(Trip $trip, TripDocument $document): Response
    {
        if ($document->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce document n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        try {
            $fileSystem = new Filesystem();
            $fileSystem->remove($document->getFile());

            $this->managerRegistry->getManager()->remove($document);
            $this->managerRegistry->getManager()->flush();

            $this->addFlash('success', 'Votre document a bien été supprimé.');
        } catch (\Exception $exception) {
            $this->addFlash('error', 'La suppression du document a échoué.');
        }

        return $this->redirectToRoute('trip_documents_bag', ['trip' => $trip->getId()]);
    }

    #[Route('/show/{document}', name: 'show', requirements: ['document' => '\d+'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function showOrDownload(Trip $trip, TripDocument $document)
    {
        if ($document->getTrip() !== $trip) {
            $this->addFlash('error', 'Ce document n\'est pas associé à ce voyage. Vous ne pouvez pas y accéder.');
            return $this->redirectToRoute('app_home');
        }

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($document->getFile())) {
            $this->addFlash('error', 'Le fichier n\'a pas été trouvé.');
            return $this->redirectToRoute('trip_documents_bag', ['trip' => $trip]);
        }

        // Détecter le type MIME
        $mimeTypes = new MimeTypes();
        $typeMime = $mimeTypes->guessMimeType($document->getFile()) ?? 'application/octet-stream';

        $response = new BinaryFileResponse($document->getFile());
        $response->headers->set('Content-Type', $typeMime);

        // Définir la disposition (inline ou attachment)
        $disposition = str_starts_with($typeMime, 'image/') || str_starts_with($typeMime, 'application/pdf')
            ? ResponseHeaderBag::DISPOSITION_INLINE
            : ResponseHeaderBag::DISPOSITION_ATTACHMENT;

        $fileName = pathinfo($document->getFile(), PATHINFO_BASENAME);
        $response->setContentDisposition($disposition, $fileName);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}