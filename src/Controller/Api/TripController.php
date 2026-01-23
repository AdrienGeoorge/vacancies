<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\TripRequestDTO;
use App\Entity\Country;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\User;
use App\Service\FileUploaderService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/trips', name: 'api_trip_')]
class TripController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry     $managerRegistry,
        private readonly FileUploaderService $uploaderService,
        private readonly TripService         $tripService
    )
    {
    }

    #[Route('/get/top', name: 'getTop', methods: ['GET'])]
    public function getTop(): JsonResponse
    {
        return $this->json($this->managerRegistry->getRepository(Trip::class)->getTopCountries());
    }

    #[Route('/future/user/{user}', name: 'future_trip_by_user', requirements: ['user' => '\d+'], methods: ['GET'])]
    public function future(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', Response::HTTP_FORBIDDEN]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/passed/user/{user}', name: 'passed_trip_by_user', requirements: ['user' => '\d+'], methods: ['GET'])]
    public function passed(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', Response::HTTP_FORBIDDEN]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/all/user/{user}', name: 'all_trip_by_user', requirements: ['user' => '\d+'], methods: ['GET'])]
    public function all(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => 'Vous ne pouvez pas voir les voyages d\'un autre utilisateur.', Response::HTTP_FORBIDDEN]);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getAllTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/get/{trip}/travelers', name: 'getTravelers', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas consulter ce voyage.', statusCode: 403)]
    public function getTravelers(?Trip $trip = null): JsonResponse
    {
        return $this->json($trip->getTripTravelers()->toArray());
    }

    #[Route('/get/{trip}/form-data', name: 'getFormData', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'Vous ne pouvez pas modifier ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null): JsonResponse
    {
        $trip = $this->managerRegistry->getRepository(Trip::class)->getOneTrip($trip->getId());

        return $this->json([
            'name' => $trip['name'],
            'description' => $trip['description'],
            'departureDate' => $trip['departureDate']?->format('Y-m-d'),
            'returnDate' => $trip['returnDate']?->format('Y-m-d'),
            'selectedCountry' => $trip['selectedCountry'],
            'image' => $trip['image']
        ]);
    }

    #[Route('/get/{trip}/general-data', name: 'getGeneralData', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas consulter ce voyage.', statusCode: 403)]
    public function getGeneralData(?Trip $trip = null): JsonResponse
    {
        return $this->json([
            'trip' => $trip,
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip)
        ]);
    }

    #[Route('/get/{trip}/dashboard', name: 'getDashboard', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas consulter ce voyage.', statusCode: 403)]
    public function getDashboard(?Trip $trip = null): JsonResponse
    {
        return $this->json([
            'countTravelers' => $trip->getTripTravelers()->count(),
            'budget' => $this->tripService->getBudget($trip),
            'planning' => $this->tripService->getPlanning($trip)
        ]);
    }

    #[Route('/get/{trip}/balance', name: 'getBalance', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'Vous ne pouvez pas consulter ce voyage.', statusCode: 403)]
    public function getBalance(?Trip $trip = null): JsonResponse
    {
        return $this->json($this->tripService->getCreditorAndDebtorDetails($trip));
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{trip}', name: 'edit', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'Vous ne pouvez pas modifier ce voyage.', statusCode: 403)]
    public function create(Request $request, ValidatorInterface $validator, ?Trip $trip = new Trip()): JsonResponse
    {
        $dto = new TripRequestDTO();
        $dto->name = $request->request->get('name');
        $dto->selectedCountry = $request->request->get('selectedCountry');
        $dto->description = $request->request->get('description');
        $dto->image = $request->files->get('image');

        try {
            $departureDateStr = $request->request->get('departureDate');
            $dto->departureDate = $departureDateStr ? new \DateTime($departureDateStr) : null;
        } catch (\Exception) {
            return $this->json(['message' => 'La date de départ est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $returnDateStr = $request->request->get('returnDate');
            $dto->returnDate = $returnDateStr ? new \DateTime($returnDateStr) : null;
        } catch (\Exception) {
            return $this->json(['message' => 'La date de retour est invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->departureDate && $dto->returnDate && $dto->departureDate > $dto->returnDate) {
            return $this->json(['message' => 'La date de retour ne peut pas être supérieure à la date de départ.'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            if ($dto->image) {
                $imageFileName = $this->uploaderService->upload($dto->image);
                $trip->setImage('/' . $this->getParameter('upload_directory') . '/' . $imageFileName);
            }

            $trip->setName($dto->name)
                ->setDescription($dto->description)
                ->setDepartureDate($dto->departureDate)
                ->setReturnDate($dto->returnDate)
                ->setCountry($this->managerRegistry->getRepository(Country::class)->findOneBy(['code' => $dto->selectedCountry]))
                ->setTraveler($this->getUser());

            if ($trip->getTripTravelers()->count() === 0) {
                $traveler = (new TripTraveler())
                    ->setName($this->getUser()->getFirstname() . ' ' . $this->getUser()->getLastname())
                    ->setTrip($trip)
                    ->setInvited($this->getUser());
                $trip->addTripTraveler($traveler);
                $this->managerRegistry->getManager()->persist($traveler);
            }

            $this->managerRegistry->getManager()->persist($trip);
            $this->managerRegistry->getManager()->flush();

            if ($request->get('_route') === 'api_trip_edit') {
                return $this->json([
                    'message' => 'Les informations de ton voyage ont bien été modifiées.',
                    'id' => $trip->getId()
                ]);
            }

            return $this->json([
                'message' => 'Ton voyage a bien été créé.',
                'id' => $trip->getId()
            ]);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création du voyage.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{trip}', name: 'delete', requirements: ['trip' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('delete_trip', subject: 'trip')]
    public function delete(Trip $trip): JsonResponse
    {
        try {
            $this->managerRegistry->getManager()->remove($trip);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => 'Votre voyage ainsi que toutes les informations associées ont bien été supprimés.']);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la suppression du voyage.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leave/{trip}', name: 'leave', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('view', subject: 'trip')]
    public function leave(Trip $trip): JsonResponse
    {
        if ($trip->getTraveler() !== $this->getUser()) {
            $traveler = $this->managerRegistry->getRepository(TripTraveler::class)->findOneBy(['trip' => $trip, 'invited' => $this->getUser()]);

            $this->managerRegistry->getManager()->remove($traveler);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => sprintf('Vous avez quitté le voyage : %s', $trip->getName())]);
        }

        return $this->json(['message' => 'Vous n\'avez pas l\'autorisation de réaliser cette action.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/update-notes/{trip}', name: 'update_notes', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', 'trip')]
    public function updateNotes(Request $request, Trip $trip): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $trip->setBlocNotes($data['blocNotes'] ?? null);

            $this->managerRegistry->getManager()->persist($trip);
            $this->managerRegistry->getManager()->flush();

            return $this->json([]);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la sauvegarde des notes.x'], Response::HTTP_BAD_REQUEST);
        }
    }
}
