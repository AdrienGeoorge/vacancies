<?php

namespace App\Controller\Api;

use App\DTO\TransportRequestDTO;
use App\Entity\Currency;
use App\Entity\PlanningEvent;
use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\CurrencyConverterService;
use App\Service\DTOService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/transports/{trip}', name: 'api_transports_', requirements: ['trip' => '\d+'])]
class TransportController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry          $managerRegistry,
        readonly TripService              $tripService,
        readonly DTOService               $dtoService,
        readonly CurrencyConverterService $converterService
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        return $this->json(
            $this->managerRegistry->getRepository(Transport::class)->findAllByTrip($trip),
            Response::HTTP_OK,
            [],
            ['datetime_format' => 'Y-m-d H:i']
        );
    }

    #[Route('/get/{transport}/form-data', name: 'getFormData', requirements: ['transport' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'Vous ne pouvez pas modifier les éléments de ce voyage.', statusCode: 403)]
    public function get(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Edition impossible : transport non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'type' => $transport->getType(),
            'company' => $transport->getCompany(),
            'description' => $transport->getDescription(),
            'departureDate' => $transport->getDepartureDate()?->format('Y-m-d H:i'),
            'departure' => $transport->getDeparture(),
            'arrivalDate' => $transport->getArrivalDate()?->format('Y-m-d H:i'),
            'destination' => $transport->getDestination(),
            'subscriptionDuration' => $transport->getSubscriptionDuration(),
            'originalPrice' => $transport->getOriginalPrice(),
            'originalCurrency' => $transport->getOriginalCurrency(),
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
        Request    $request,
        ?Trip      $trip = null,
        ?Transport $transport = new Transport()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ? $this->managerRegistry->getRepository(TransportType::class)->find($data['type']) : null;
        $selectedCurrency = isset($data['originalCurrency']) ? $this->managerRegistry->getRepository(Currency::class)
            ->findOneBy(['code' => $data['originalCurrency']]) : null;

        $dto = new TransportRequestDTO($type, $selectedCurrency);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates(
                $trip,
                $dto->departureDate ?? null,
                $dto->arrivalDate ?? null
            );

            if ($errorOnCompare === null) {
                $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

                $transport->setTrip($trip);
                $transport->setPrice($dto->originalPrice);
                $transport = $this->dtoService->mapToEntity($dto, $transport);

                if (
                    $transport->getType()->getName() !== 'Voiture' &&
                    $eurCurrency !== $dto->originalCurrency &&
                    $transport->getOriginalPrice() !== $dto->originalPrice
                ) {
                    $convertedDeposit = $this->converterService->convert($dto->originalPrice, $dto->originalCurrency, $eurCurrency);
                    $transport->setConvertedPrice($convertedDeposit['amount']);
                    $transport->setExchangeRate($convertedDeposit['rate']);
                    $transport->setConvertedAt(new \DateTimeImmutable());
                }

                $this->managerRegistry->getManager()->persist($transport);
                $this->managerRegistry->getManager()->flush();

                if ($request->get('_route') === 'api_transports_edit') {
                    return $this->json(['message' => 'Les informations de ton moyen de transport ont bien été modifiées.']);
                }

                return $this->json(['message' => 'Ce moyen de transport a bien été ajouté à votre voyage.']);
            } else {
                return $this->json(['message' => $errorOnCompare], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception) {
            return $this->json(['message' => 'Une erreur est survenue lors de la création de ce moyen de transport.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{transport}', name: 'delete', requirements: ['transport' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Suppression impossible : transport non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['transport' => $transport]);
        if ($event) $this->managerRegistry->getManager()->remove($event);

        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => 'Votre moyen de transport a bien été dissocié de ce voyage et supprimé.']);
    }

    #[Route('/update-reserved/{transport}', name: 'update_reserved', requirements: ['transport' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => 'Modification impossible : transport non trouvé.'], Response::HTTP_NOT_FOUND);
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