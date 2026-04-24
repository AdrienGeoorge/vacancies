<?php

namespace App\Controller\Api;

use App\DTO\TransportRequestDTO;
use App\Entity\Currency;
use App\Entity\PlanningEvent;
use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\BudgetAlertService;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/transports/{trip}', name: 'api_transports_', requirements: ['trip' => '\d+'])]
class TransportController extends AbstractController
{
    public function __construct(
        readonly ManagerRegistry          $managerRegistry,
        readonly TripService              $tripService,
        readonly DTOService               $dtoService,
        readonly CurrencyConverterService $converterService,
        readonly BudgetAlertService       $budgetAlertService,
        readonly TranslatorInterface      $translator
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        $transports = $this->managerRegistry->getRepository(Transport::class)->findAllByTrip($trip);

        /** @var Transport $transport */
        foreach ($transports as $transport) {
            $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

            if ($transport->getType()->getName() === 'Voiture' && !$transport->isRental()) {
                $estimatedGasoline = $transport->getEstimatedGasoline();
                $estimatedToll = $transport->getEstimatedToll();

                if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                    $estimatedGasoline = $this->converterService->convert(
                        $transport->getEstimatedGasoline(),
                        $eurCurrency,
                        $trip->getCurrency(),
                        $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                    )['amount'];

                    $estimatedToll = $this->converterService->convert(
                        $transport->getEstimatedToll(),
                        $eurCurrency,
                        $trip->getCurrency(),
                        $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                    )['amount'];
                }

                $transport->setFinalEstimatedGasoline($estimatedGasoline);
                $transport->setFinalEstimatedToll($estimatedToll);
            }

            $finalPrice = $transport->getTotalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $finalPrice = $this->converterService->convert(
                    $finalPrice,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $transport->getConvertedAt() ?? $transport->getPurchaseDate()
                )['amount'];
            }

            $transport->setFinalPrice($finalPrice);
        }

        return $this->json(
            $transports,
            Response::HTTP_OK,
            [],
            ['datetime_format' => 'Y-m-d H:i']
        );
    }

    #[Route('/get/{transport}/form-data', name: 'getFormData', requirements: ['transport' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function get(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => $this->translator->trans('transport.edit.not_found')], Response::HTTP_NOT_FOUND);
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
            'isRental' => $transport->isRental(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{transport}', name: 'edit', requirements: ['transport' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
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

                $oldTotal = $this->budgetAlertService->getCategoryTotal($trip, 'transports');

                $transport->setPurchaseDate(new \DateTime());
                $transport->setTrip($trip);
                $transport->setPrice($dto->originalPrice);
                $transport = $this->dtoService->mapToEntity($dto, $transport);
                $transport->setIsRental((bool)($dto->isRental ?? false));

                if (
                    ($transport->getType()->getName() !== 'Voiture' || $transport->isRental()) &&
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

                $toastMessage = $this->budgetAlertService->checkAndNotify($trip, 'transports', $oldTotal);

                if ($request->get('_route') === 'api_transports_edit') {
                    return $this->json([
                        'message' => $this->translator->trans('transport.updated'),
                        'warning' => $toastMessage
                    ]);
                }

                return $this->json([
                    'message' => $this->translator->trans('transport.created'),
                    'warning' => $toastMessage
                ]);
            } else {
                return $this->json(['message' => $errorOnCompare], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('transport.error.create')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{transport}', name: 'delete', requirements: ['transport' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => $this->translator->trans('transport.delete.not_found')], Response::HTTP_NOT_FOUND);
        }

        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['transport' => $transport]);
        if ($event) $this->managerRegistry->getManager()->remove($event);

        $this->managerRegistry->getManager()->remove($transport);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('transport.deleted')]);
    }

    #[Route('/update-reserved/{transport}', name: 'update_reserved', requirements: ['transport' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Transport $transport = null): JsonResponse
    {
        if (!$transport) {
            return $this->json(['message' => $this->translator->trans('transport.update.not_found')], Response::HTTP_NOT_FOUND);
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

        return $this->json(['message' => $this->translator->trans('transport.toggle_reserved')]);
    }
}