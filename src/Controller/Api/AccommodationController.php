<?php

namespace App\Controller\Api;

use App\Entity\Accommodation;
use App\Entity\Currency;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Service\AccommodationService;
use App\Service\BudgetAlertService;
use App\Service\CurrencyConverterService;
use App\Service\TripService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/accommodations/{trip}', name: 'api_accommodations_', requirements: ['trip' => '\d+'])]
class AccommodationController extends AbstractController
{

    public function __construct(
        readonly ManagerRegistry      $managerRegistry,
        readonly AccommodationService $accommodationService,
        readonly TripService          $tripService,
        readonly BudgetAlertService   $budgetAlertService,
        readonly TranslatorInterface  $translator,
        readonly CurrencyConverterService $converterService
    )
    {
    }

    #[Route('/get-all', name: 'get_all', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip')]
    public function getAll(?Trip $trip = null): JsonResponse
    {
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $accommodations = $this->managerRegistry->getRepository(Accommodation::class)->findAllByTrip($trip);

        /** @var Accommodation $accommodation */
        foreach ($accommodations as $accommodation) {
            [$location, $deposit, $total] = $accommodation->getTotalPrices();

            foreach ($accommodation->getAdditionalExpensive() as $additionalExpensive) {
                $calculatedPrice = $additionalExpensive->getOriginalCurrency()?->getCode() !== 'EUR'
                    ? $additionalExpensive->getConvertedPrice()
                    : $additionalExpensive->getOriginalPrice();

                $additionalExpensive->setFinalPrice($calculatedPrice);
            }

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $location = $this->converterService->convert(
                    $location,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                )['amount'];

                if ($deposit) {
                    $deposit = $this->converterService->convert(
                        $deposit,
                        $eurCurrency,
                        $trip->getCurrency(),
                        $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                    )['amount'];
                }

                foreach ($accommodation->getAdditionalExpensive() as $additionalExpensive) {
                    $additionalExpensive->setFinalPrice(
                        $this->converterService->convert(
                            $additionalExpensive->getFinalPrice(),
                            $eurCurrency,
                            $trip->getCurrency(),
                            $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                        )['amount']
                    );
                }

                $total = $this->converterService->convert(
                    $total,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $accommodation->getConvertedAt() ?? $accommodation->getPurchaseDate()
                )['amount'];
            };

            $accommodation->setFinalLocationPrice($location);
            $accommodation->setFinalDepositPrice($deposit);
            $accommodation->setFinalPrice($total);
        }

        return $this->json($accommodations);
    }

    #[Route('/get/{accommodation}/form-data', name: 'getFormData', requirements: ['accommodation' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function get(?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => $this->translator->trans('accommodation.edit.not_found')], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'name' => $accommodation->getName(),
            'address' => $accommodation->getAddress(),
            'zipCode' => $accommodation->getZipCode(),
            'city' => $accommodation->getCity(),
            'country' => $accommodation->getCountry(),
            'arrivalDate' => $accommodation->getArrivalDate()?->format('Y-m-d'),
            'departureDate' => $accommodation->getDepartureDate()?->format('Y-m-d'),
            'description' => $accommodation->getDescription(),
            'originalPrice' => $accommodation->getOriginalPrice(),
            'originalCurrency' => $accommodation->getOriginalCurrency(),
            'originalDeposit' => $accommodation->getOriginalDeposit(),
            'originalDepositCurrency' => $accommodation->getOriginalDepositCurrency(),
            'additionalExpensive' => $accommodation->getAdditionalExpensive(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{accommodation}', name: 'edit', requirements: ['accommodation' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function create(Request $request, ?Trip $trip = null, ?Accommodation $accommodation = new Accommodation()): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        [$dto, $errors, $sentIds] = $this->accommodationService->initDtoFromRequest($data);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                return $this->json(['message' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $dto->arrivalDate, $dto->departureDate);

            if ($errorOnCompare === null) {
                $oldTotal = $this->budgetAlertService->getCategoryTotal($trip, 'accommodations');

                $this->accommodationService->handleAccommodationForm($trip, $accommodation, $dto, $sentIds);

                $toastMessage = $this->budgetAlertService->checkAndNotify($trip, 'accommodations', $oldTotal);

                if ($request->get('_route') === 'api_accommodations_edit') {
                    return $this->json([
                        'message' => $this->translator->trans('accommodation.updated'),
                        'warning' => $toastMessage
                    ]);
                }

                return $this->json([
                    'message' => $this->translator->trans('accommodation.created'),
                    'warning' => $toastMessage
                ]);
            } else {
                return $this->json(['message' => $errorOnCompare], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception) {
            return $this->json(['message' => $this->translator->trans('accommodation.error.create')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{accommodation}', name: 'delete', requirements: ['accommodation' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => $this->translator->trans('accommodation.delete.not_found')], Response::HTTP_NOT_FOUND);
        }

        $this->managerRegistry->getManager()->remove($accommodation);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('accommodation.deleted')]);
    }

    #[Route('/update-reserved/{accommodation}', name: 'update_reserved', requirements: ['accommodation' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Accommodation $accommodation = null): JsonResponse
    {
        if (!$accommodation) {
            return $this->json(['message' => $this->translator->trans('accommodation.update.not_found')], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $accommodation->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $accommodation->setPayedBy(null);
        }

        $accommodation->setBooked(!$accommodation->isBooked());

        $this->managerRegistry->getManager()->persist($accommodation);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('accommodation.toggle_reserved')]);
    }
}