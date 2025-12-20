<?php

namespace App\Controller\Api;

use App\DTO\AccommodationAdditionalRequestDTO;
use App\DTO\TransportRequestDTO;
use App\Entity\Accommodation;
use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\AccommodationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transports/{trip}', name: 'api_transports_', requirements: ['trip' => '\d+'])]
class TransportController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry      $managerRegistry,
        readonly AccommodationService $accommodationService
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        return $this->json(
            $this->managerRegistry->getRepository(Transport::class)->findAllByTrip($trip)
        );
    }

    #[Route('/get/{transport}/form-data', name: 'getFormData', requirements: ['transport' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Edition impossible : transport non trouvé.'], 404);
        }

        return $this->json([
            'type' => $transport->getType(),
            'company' => $transport->getCompany(),
            'description' => $transport->getDescription(),
            'departureDate' => $transport->getDepartureDate()?->format('Y-m-d'),
            'departure' => $transport->getDeparture(),
            'arrivalDate' => $transport->getArrivalDate()?->format('Y-m-d'),
            'destination' => $transport->getDestination(),
            'subscriptionDuration' => $transport->getSubscriptionDuration(),
            'price' => $transport->getPrice(),
            'perPerson' => $transport->isPerPerson(),
            'estimatedToll' => $transport->getEstimatedToll(),
            'estimatedGasoline' => $transport->getEstimatedGasoline(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{transport}', name: 'edit', requirements: ['transport' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function create(
        Request            $request,
        ValidatorInterface $validator,
        ?Trip              $trip = null,
        ?Transport         $transport = new Transport()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $selectedType = $data['selectedType'] ? $this->managerRegistry->getRepository(TransportType::class)->find($data['selectedType']) : null;

        $dto = new TransportRequestDTO($selectedType);
        $errors = new ConstraintViolationList();

        foreach ($data as $key => $value) {
            if ($key === 'selectedType') continue;

            if ($key === 'departureDate' || $key === 'arrivalDate') {
                try {
                    $dto->{$key} = $value ? new \DateTime($value) : null;
                } catch (\Exception) {
                    $errors->add(new ConstraintViolation('La date est invalide.', '', [], null, $key, null));
                }
            } else {
                $dto->{$key} = $value;
            }
        }

        $errors->addAll($validator->validate($dto));

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], 400);
            }
        }

        try {
            $transport->setTrip($trip);
            $transport->setCompany($dto->company);
            $transport->setDescription($dto->description);
            $transport->setDepartureDate($dto->departureDate ?? null);
            $transport->setDeparture($dto->departure ?? null);
            $transport->setArrivalDate($dto->arrivalDate ?? null);
            $transport->setDestination($dto->destination ?? null);
            $transport->setSubscriptionDuration($dto->subscriptionDuration ?? null);
            $transport->setPrice($dto->price ?? 0);
            $transport->setPerPerson($dto->perPerson);
            $transport->setEstimatedToll($dto->estimatedToll ?? null);
            $transport->setEstimatedGasoline($dto->estimatedGasoline ?? null);
            $transport->setType($dto->type);

            $this->managerRegistry->getManager()->persist($transport);
            $this->managerRegistry->getManager()->flush();

            if ($request->get('_route') === 'api_transports_edit') {
                return $this->json(['message' => 'Les informations de ton moyen de transport ont bien été modifiées.']);
            }

            return $this->json(['message' => 'Ce moyen de transport a bien été ajouté à votre voyage.']);
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de ce moyen de transport.'], 400);
        }
    }

    #[Route('/delete/{transport}', name: 'delete', requirements: ['transport' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Suppression impossible : transport non trouvé.'], 404);
        }

        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Transport supprimé avec succès.']);
    }

    #[Route('/update-reserved/{transport}', name: 'update_reserved', requirements: ['transport' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Modification impossible : transport non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $transport->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $transport->setPayedBy(null);
        }

        $transport->setPaid(!$transport->isPaid());

        $this->managerRegistry->getManager()->persist($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Moyen de transport modifié avec succès.']);
    }
}