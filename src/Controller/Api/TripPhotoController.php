<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\TripPhotoRequestDTO;
use App\Entity\Trip;
use App\Entity\TripPhoto;
use App\Service\FileUploaderService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/trip-photos/{trip}', name: 'api_trip_photos_', requirements: ['trip' => '\\d+'])]
class TripPhotoController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry     $managerRegistry,
        private readonly FileUploaderService $uploaderService,
        private readonly string              $photosDirectory,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas accéder aux photos de ce voyage.', statusCode: 403)]
    public function list(Trip $trip): JsonResponse
    {
        $photos = $trip->getPhotos()
            ->filter(fn(TripPhoto $p) => !$p->isStory())
            ->map(fn(TripPhoto $p) => $this->serializePhoto($p))
            ->getValues();

        return $this->json($photos);
    }

    #[Route('/stories', name: 'list_stories', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas accéder aux stories de ce voyage.', statusCode: 403)]
    public function listStories(Trip $trip): JsonResponse
    {
        $stories = $trip->getPhotos()
            ->filter(fn(TripPhoto $p) => $p->isActiveStory())
            ->map(fn(TripPhoto $p) => $this->serializePhoto($p))
            ->getValues();

        return $this->json($stories);
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function upload(
        Request            $request,
        Trip               $trip,
        ValidatorInterface $validator,
    ): JsonResponse {
        return $this->handleUpload($request, $trip, $validator, expiresAt: null);
    }

    #[Route('/upload-story', name: 'upload_story', methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function uploadStory(
        Request            $request,
        Trip               $trip,
        ValidatorInterface $validator,
    ): JsonResponse {
        return $this->handleUpload($request, $trip, $validator, expiresAt: new \DateTimeImmutable('+24 hours'));
    }

    private function handleUpload(
        Request             $request,
        Trip                $trip,
        ValidatorInterface  $validator,
        ?\DateTimeImmutable $expiresAt,
    ): JsonResponse {
        $dto = new TripPhotoRequestDTO();
        $dto->caption = $request->request->get('caption');
        $dto->file = $request->files->get('file');

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $directory = $this->photosDirectory . '/' . $trip->getId();
            $fileName = $this->uploaderService->upload($dto->file, null, $directory);

            $photo = new TripPhoto();
            $photo->setFile($directory . '/' . $fileName);
            $photo->setCaption($dto->caption);
            $photo->setUploadedBy($this->getUser());
            $photo->setUploadedAt(new \DateTimeImmutable());
            $photo->setExpiresAt($expiresAt);
            $photo->setTrip($trip);

            $em = $this->managerRegistry->getManager();
            $em->persist($photo);
            $em->flush();

            return $this->json($this->serializePhoto($photo), Response::HTTP_CREATED);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de l\'upload de la photo.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{photo}/caption', name: 'update_caption', requirements: ['photo' => '\\d+'], methods: ['PATCH'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function updateCaption(Trip $trip, TripPhoto $photo, Request $request): JsonResponse
    {
        if ($photo->getTrip() !== $trip) {
            return $this->json(['message' => 'Cette photo n\'est pas associée à ce voyage.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $photo->setCaption($data['caption'] ?? null);

        $this->managerRegistry->getManager()->flush();

        return $this->json($this->serializePhoto($photo));
    }

    #[Route('/{photo}', name: 'delete', requirements: ['photo' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function delete(Trip $trip, TripPhoto $photo): JsonResponse
    {
        if ($photo->getTrip() !== $trip) {
            return $this->json(['message' => 'Cette photo n\'est pas associée à ce voyage.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $filesystem = new Filesystem();
            if ($filesystem->exists($photo->getFile())) {
                $filesystem->remove($photo->getFile());
            }

            $em = $this->managerRegistry->getManager();
            $em->remove($photo);
            $em->flush();

            return $this->json(['message' => 'La photo a bien été supprimée.']);
        } catch (\Exception) {
            return $this->json(['message' => 'La suppression de la photo a échoué.'], Response::HTTP_BAD_REQUEST);
        }
    }

    private function serializePhoto(TripPhoto $photo): array
    {
        return [
            'id'         => $photo->getId(),
            'file'       => $photo->getFile(),
            'caption'    => $photo->getCaption(),
            'isStory'    => $photo->isStory(),
            'expiresAt'  => $photo->getExpiresAt()?->format('Y-m-d H:i:s'),
            'uploadedAt' => $photo->getUploadedAt()?->format('Y-m-d H:i:s'),
            'uploadedBy' => $photo->getUploadedBy() ? [
                'id'        => $photo->getUploadedBy()->getId(),
                'firstname' => $photo->getUploadedBy()->getCompleteName(),
            ] : null,
        ];
    }
}
