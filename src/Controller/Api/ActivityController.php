<?php

namespace App\Controller\Api;

use App\DTO\ActivityRequestDTO;
use App\Entity\Activity;
use App\Entity\Currency;
use App\Entity\EventType;
use App\Entity\PlanningEvent;
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

#[Route('/api/activities/{trip}', name: 'api_activities_', requirements: ['trip' => '\d+'])]
class ActivityController extends AbstractController
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
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);
        $activities = $this->managerRegistry->getRepository(Activity::class)->findAllByTrip($trip);

        /** @var Activity $activity */
        foreach ($activities as $activity) {
            $calculatedPrice = $activity->getOriginalCurrency()?->getCode() !== 'EUR'
                ? $activity->getConvertedPrice()
                : $activity->getOriginalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $calculatedPrice = $this->converterService->convert(
                    $calculatedPrice,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $activity->getConvertedAt() ?? $activity->getPurchaseDate()
                )['amount'];
            }

            $activity->setFinalPrice($calculatedPrice);
        }

        return $this->json(
            $activities,
            Response::HTTP_OK,
            [],
            ['datetime_format' => 'Y-m-d H:i']
        );
    }

    #[Route('/get/{activity}/form-data', name: 'getFormData', requirements: ['activity' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function get(?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => $this->translator->trans('activity.edit.not_found')], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'type' => $activity->getType(),
            'name' => $activity->getName(),
            'description' => $activity->getDescription(),
            'date' => $activity->getDate()?->format('Y-m-d H:i'),
            'originalPrice' => $activity->getOriginalPrice(),
            'originalCurrency' => $activity->getOriginalCurrency(),
            'perPerson' => $activity->isPerPerson()
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{activity}', name: 'edit', requirements: ['activity' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function create(
        Request   $request,
        ?Trip     $trip = null,
        ?Activity $activity = new Activity()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ? $this->managerRegistry->getRepository(EventType::class)->find($data['type']) : null;
        $selectedCurrency = isset($data['originalCurrency']) ? $this->managerRegistry->getRepository(Currency::class)
            ->findOneBy(['code' => $data['originalCurrency']]) : null;

        $dto = new ActivityRequestDTO($type, $selectedCurrency);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $errorOnCompare = $this->tripService->compareElementDateBetweenTripDates($trip, $dto->date);

            if ($errorOnCompare === null) {
                $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

                $oldTotal = $this->budgetAlertService->getCategoryTotal($trip, 'activities');

                $activity->setPurchaseDate(new \DateTime());
                $activity->setTrip($trip);
                $activity->setPrice($dto->originalPrice);
                $activity = $this->dtoService->mapToEntity($dto, $activity);

                if ($eurCurrency !== $dto->originalCurrency && $activity->getOriginalPrice() !== $dto->originalPrice) {
                    $convertedDeposit = $this->converterService->convert($dto->originalPrice, $dto->originalCurrency, $eurCurrency);
                    $activity->setConvertedPrice($convertedDeposit['amount']);
                    $activity->setExchangeRate($convertedDeposit['rate']);
                    $activity->setConvertedAt(new \DateTimeImmutable());
                }

                $this->managerRegistry->getManager()->persist($activity);

                if ($activity->getDate()) {
                    $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);

                    if (!$event) {
                        $event = (new PlanningEvent())
                            ->setTrip($trip)
                            ->setActivity($activity);
                    }

                    $event->setType($activity->getType());
                    $event->setDescription($activity->getDescription());
                    $event->setTitle($activity->getName());
                    $event->setStart($activity->getDate());

                    $this->managerRegistry->getManager()->persist($event);
                }

                $this->managerRegistry->getManager()->flush();

                $toastMessage = $this->budgetAlertService->checkAndNotify($trip, 'activities', $oldTotal);

                if ($request->get('_route') === 'api_activities_edit') {
                    return $this->json([
                        'message' => $this->translator->trans('activity.updated'),
                        'warning' => $toastMessage
                    ]);
                }

                return $this->json([
                    'message' => $this->translator->trans('activity.created'),
                    'warning' => $toastMessage
                ]);
            } else {
                return $this->json(['message' => $errorOnCompare], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return $this->json(['message' => $this->translator->trans('activity.error.create')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{activity}', name: 'delete', requirements: ['activity' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => $this->translator->trans('activity.delete.not_found')], Response::HTTP_NOT_FOUND);
        }

        $event = $this->managerRegistry->getRepository(PlanningEvent::class)->findOneBy(['activity' => $activity]);
        if ($event) $this->managerRegistry->getManager()->remove($event);

        $this->managerRegistry->getManager()->remove($activity);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('activity.deleted')]);
    }

    #[Route('/update-reserved/{activity}', name: 'update_reserved', requirements: ['activity' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?Activity $activity = null): JsonResponse
    {
        if (!$activity) {
            return $this->json(['message' => $this->translator->trans('activity.update.not_found')], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $activity->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $activity->setPayedBy(null);
        }

        $activity->setBooked(!$activity->isBooked());

        $this->managerRegistry->getManager()->persist($activity);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('activity.toggle_reserved')]);
    }
}