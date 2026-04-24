<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\TripRequestDTO;
use App\Entity\Country;
use App\Entity\Currency;
use App\Entity\Trip;
use App\Entity\TripDestination;
use App\Entity\TripReimbursement;
use App\Entity\TripTraveler;
use App\Entity\User;
use App\Service\FileUploaderService;
use App\Service\TripService;
use App\Service\WeatherService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/trips', name: 'api_trip_')]
class TripController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry     $managerRegistry,
        private readonly FileUploaderService $uploaderService,
        private readonly TripService         $tripService,
        private readonly WeatherService      $weatherService,
        private readonly TranslatorInterface $translator
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
            return $this->json(['message' => $this->translator->trans('trip.forbidden.view_other')], Response::HTTP_FORBIDDEN);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getFutureTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/passed/user/{user}', name: 'passed_trip_by_user', requirements: ['user' => '\d+'], methods: ['GET'])]
    public function passed(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => $this->translator->trans('trip.forbidden.view_other')], Response::HTTP_FORBIDDEN);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getPassedTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/all/user/{user}', name: 'all_trip_by_user', requirements: ['user' => '\d+'], methods: ['GET'])]
    public function all(User $user): JsonResponse
    {
        if ($this->getUser() !== $user) {
            return $this->json(['message' => $this->translator->trans('trip.forbidden.view_other')], Response::HTTP_FORBIDDEN);
        }

        $trips = $this->managerRegistry->getRepository(Trip::class)->getAllTrips($this->getUser());

        return $this->json($trips);
    }

    #[Route('/get/{trip}/travelers', name: 'getTravelers', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function getTravelers(?Trip $trip = null): JsonResponse
    {
        return $this->json($trip->getTripTravelers()->toArray());
    }

    #[Route('/get/{trip}/form-data', name: 'getFormData', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'trip.access.edit_denied', statusCode: 403)]
    public function get(?Trip $trip = null): JsonResponse
    {
        $trip = $this->managerRegistry->getRepository(Trip::class)->getOneTrip($trip->getId());

        return $this->json([
            'name' => $trip['name'],
            'description' => $trip['description'],
            'departureDate' => $trip['departureDate'],
            'returnDate' => $trip['returnDate'],
            'destinations' => $trip['destinations'],
            'image' => $trip['image'],
            'currency' => $trip['currency'],
        ]);
    }

    #[Route('/get/{trip}/general-data', name: 'getGeneralData', requirements: ['trip' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function getGeneralData(?Trip $trip = null): JsonResponse
    {
        return $this->json([
            'trip' => $trip,
            'cities' => $this->weatherService->getCities($trip),
            'countDaysBeforeOrAfter' => $this->tripService->countDaysBeforeOrAfter($trip)
        ]);
    }

    #[Route('/get/{trip}/dashboard', name: 'getDashboard', requirements: ['trip' => '\d+'], methods: ['GET'])]
//    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function getDashboard(?Trip $trip = null): JsonResponse
    {
        return $this->json([
            'countTravelers' => $trip->getTripTravelers()->count(),
            'budget' => $this->tripService->getBudget($trip),
            'planning' => $this->tripService->getPlanning($trip)
        ]);
    }

    #[Route('/get/{trip}/balance', name: 'getBalance', requirements: ['trip' => '\d+'], methods: ['GET'])]
//    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function getBalance(?Trip $trip = null): JsonResponse
    {
        $data = $this->tripService->getCreditorAndDebtorDetails($trip);
        $data['reimbursements'] = array_map(fn($r) => [
            'id' => $r->getId(),
            'from' => $r->getFromTraveler()->getName(),
            'fromId' => $r->getFromTraveler()->getId(),
            'to' => $r->getToTraveler()->getName(),
            'toId' => $r->getToTraveler()->getId(),
            'amount' => $r->getAmount(),
            'description' => $r->getDescription(),
            'date' => $r->getDate()?->format('Y-m-d'),
        ], $trip->getReimbursements()->toArray());
        return $this->json($data);
    }

    #[Route('/get/{trip}/reimbursements', name: 'createReimbursement', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_denied', statusCode: 403)]
    public function createReimbursement(Request $request, Trip $trip): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $fromTraveler = $this->managerRegistry->getRepository(TripTraveler::class)->find($data['fromTraveler'] ?? null);
            $toTraveler = $this->managerRegistry->getRepository(TripTraveler::class)->find($data['toTraveler'] ?? null);

            if (!$fromTraveler || $fromTraveler->getTrip() !== $trip) {
                return $this->json(['message' => $this->translator->trans('trip.reimbursement.traveler_source_invalid')], Response::HTTP_BAD_REQUEST);
            }
            if (!$toTraveler || $toTraveler->getTrip() !== $trip) {
                return $this->json(['message' => $this->translator->trans('trip.reimbursement.traveler_dest_invalid')], Response::HTTP_BAD_REQUEST);
            }
            if (!isset($data['amount']) || (float) $data['amount'] <= 0) {
                return $this->json(['message' => $this->translator->trans('trip.reimbursement.amount_invalid')], Response::HTTP_BAD_REQUEST);
            }

            $date = null;
            if (!empty($data['date'])) {
                try {
                    $date = new \DateTimeImmutable($data['date']);
                } catch (\Exception) {
                }
            }

            $reimbursement = (new TripReimbursement())
                ->setTrip($trip)
                ->setFromTraveler($fromTraveler)
                ->setToTraveler($toTraveler)
                ->setAmount((string) $data['amount'])
                ->setDescription($data['description'] ?? null)
                ->setDate($date);

            $this->managerRegistry->getManager()->persist($reimbursement);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('trip.reimbursement.saved'), 'id' => $reimbursement->getId()], Response::HTTP_CREATED);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.error.generic')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/get/{trip}/reimbursements/{reimbursement}', name: 'deleteReimbursement', requirements: ['trip' => '\d+', 'reimbursement' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_denied', statusCode: 403)]
    public function deleteReimbursement(Trip $trip, TripReimbursement $reimbursement): JsonResponse
    {
        if ($reimbursement->getTrip() !== $trip) {
            return $this->json(['message' => $this->translator->trans('trip.reimbursement.not_in_trip')], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->managerRegistry->getManager()->remove($reimbursement);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('trip.reimbursement.deleted')]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.error.generic')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{trip}', name: 'edit', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_trip', subject: 'trip', message: 'trip.access.edit_denied', statusCode: 403)]
    public function create(Request $request, ValidatorInterface $validator, ?Trip $trip = new Trip()): JsonResponse
    {
        $dto = new TripRequestDTO();
        $dto->name = $request->request->get('name');
        $dto->destinations = $request->request->all()['destinations'] ?? [];
        $dto->description = $request->request->get('description');
        $dto->image = $request->files->get('image');

        try {
            $departureDateStr = $request->request->get('departureDate');
            $dto->departureDate = $departureDateStr ? new \DateTime($departureDateStr) : null;
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.date.invalid_departure')], Response::HTTP_BAD_REQUEST);
        }

        try {
            $returnDateStr = $request->request->get('returnDate');
            $dto->returnDate = $returnDateStr ? new \DateTime($returnDateStr) : null;
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.date.invalid_return')], Response::HTTP_BAD_REQUEST);
        }

        if ($dto->departureDate && $dto->returnDate && $dto->departureDate > $dto->returnDate) {
            return $this->json(['message' => $this->translator->trans('trip.date.return_before_departure')], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $isEdit = $request->get('_route') === 'api_trip_edit';

            if ($dto->image) {
                if ($trip->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $trip->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $imageFileName = $this->uploaderService->upload($dto->image);
                $trip->setImage('/' . $this->getParameter('upload_directory') . '/' . $imageFileName);
            } elseif ($request->request->get('removeImage') && $trip->getImage()) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $trip->getImage();
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
                $trip->setImage(null);
            }

            $currencyCode = $request->request->get('currency', 'EUR');
            $currency = $this->managerRegistry->getRepository(Currency::class)->find($currencyCode);
            if (!$currency) {
                return $this->json(['message' => $this->translator->trans('trip.currency.invalid')], Response::HTTP_BAD_REQUEST);
            }

            $trip->setName($dto->name)
                ->setDescription($dto->description)
                ->setDepartureDate($dto->departureDate)
                ->setReturnDate($dto->returnDate)
                ->setTraveler($this->getUser())
                ->setCurrency($currency);

            if ($isEdit) {
                // Récupérer les destinations actuelles du voyage
                $existingDestinations = $trip->getDestinations();
                $existingCountryIds = [];
                $destinationsById = [];

                foreach ($existingDestinations as $dest) {
                    $countryId = $dest->getCountry()->getId();
                    $existingCountryIds[] = $countryId;
                    $destinationsById[$countryId] = $dest;
                }

                // Récupérer les nouveaux pays depuis le formulaire
                $newCountryIds = [];
                foreach ($dto->destinations as $destinationData) {
                    $countryIri = $destinationData['country'] ?? '';
                    $countryId = (int)basename($countryIri);
                    $newCountryIds[] = $countryId;
                }

                // Trouver les pays à supprimer (présents avant mais plus maintenant)
                $toDelete = array_diff($existingCountryIds, $newCountryIds);
                foreach ($toDelete as $countryId) {
                    if (isset($destinationsById[$countryId])) {
                        $trip->removeDestination($destinationsById[$countryId]);
                        $this->managerRegistry->getManager()->remove($destinationsById[$countryId]);
                    }
                }

                // Trouver les pays à ajouter (nouveaux)
                $toAdd = array_diff($newCountryIds, $existingCountryIds);

                // Traiter toutes les destinations dans l'ordre
                foreach ($dto->destinations as $index => $destinationData) {
                    $countryIri = $destinationData['country'] ?? '';
                    $countryId = (int)basename($countryIri);

                    $country = $this->managerRegistry->getRepository(Country::class)->find($countryId);
                    if (!$country) {
                        return $this->json([
                            'message' => $this->translator->trans('trip.country.invalid')
                        ], Response::HTTP_BAD_REQUEST);
                    }

                    // Si c'est une nouvelle destination
                    if (in_array($countryId, $toAdd)) {
                        $destination = new TripDestination();
                        $destination->setTrip($trip)
                            ->setCountry($country)
                            ->setDisplayOrder($index);

                        $trip->addDestination($destination);
                        $this->managerRegistry->getManager()->persist($destination);
                    } // Si c'est une destination existante, juste mettre à jour l'ordre
                    else if (isset($destinationsById[$countryId])) {
                        $destinationsById[$countryId]->setDisplayOrder($index);
                        $this->managerRegistry->getManager()->persist($destinationsById[$countryId]);
                    }
                }

                $this->managerRegistry->getManager()->flush();
            } else {
                // Mode création
                foreach ($dto->destinations as $destinationData) {
                    $countryIri = $destinationData['country'] ?? '';
                    $countryId = (int)basename($countryIri);

                    $country = $this->managerRegistry->getRepository(Country::class)->find($countryId);

                    if (!$country) {
                        return $this->json([
                            'message' => $this->translator->trans('trip.country.invalid')
                        ], Response::HTTP_BAD_REQUEST);
                    }

                    $destination = new TripDestination();
                    $destination->setTrip($trip)
                        ->setCountry($country)
                        ->setDisplayOrder((int)($destinationData['displayOrder'] ?? 0));

                    $trip->addDestination($destination);
                    $this->managerRegistry->getManager()->persist($destination);
                }
            }

            if ($trip->getDestinations()->count() === 1 && $trip->getDepartureDate() && $trip->getReturnDate()) {
                $trip->getDestinations()->first()
                    ->setDepartureDate($trip->getDepartureDate())
                    ->setReturnDate($trip->getReturnDate());
            }

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

            if ($isEdit) {
                return $this->json([
                    'message' => $this->translator->trans('trip.updated'),
                    'id' => $trip->getId()
                ]);
            }

            return $this->json([
                'message' => $this->translator->trans('trip.created'),
                'id' => $trip->getId()
            ]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.error.create')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{trip}', name: 'delete', requirements: ['trip' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('delete_trip', subject: 'trip')]
    public function delete(Trip $trip): JsonResponse
    {
        try {
            $this->managerRegistry->getManager()->remove($trip);
            $this->managerRegistry->getManager()->flush();

            return $this->json(['message' => $this->translator->trans('trip.deleted')]);
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.error.delete')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/leave/{trip}', name: 'leave', requirements: ['trip' => '\d+'], methods: ['POST'])]
    #[IsGranted('view', subject: 'trip')]
    public function leave(Trip $trip): JsonResponse
    {
        if ($trip->getTraveler() !== $this->getUser()) {
            $traveler = $this->managerRegistry->getRepository(TripTraveler::class)->findOneBy(['trip' => $trip, 'invited' => $this->getUser()]);

            try {
                $this->managerRegistry->getManager()->remove($traveler);
                $this->managerRegistry->getManager()->flush();
            } catch (\Exception) {
                return $this->json([
                    'message' => $this->translator->trans('trip.leave.error')
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json(['message' => $this->translator->trans('trip.left', ['%name%' => $trip->getName()])]);
        }

        return $this->json(['message' => $this->translator->trans('trip.unauthorized_action')], Response::HTTP_FORBIDDEN);
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

            return $this->json($trip->getBlocNotes());
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('trip.notes.error')], Response::HTTP_BAD_REQUEST);
        }
    }

}
