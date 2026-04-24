<?php

namespace App\Controller\Api;

use App\DTO\VariousExpensiveRequestDTO;
use App\Entity\Currency;
use App\Entity\Trip;
use App\Entity\TripTraveler;
use App\Entity\VariousExpensive;
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

#[Route('/api/various-expensive/{trip}', name: 'api_various_expensive_', requirements: ['trip' => '\d+'])]
class VariousExpensiveController extends AbstractController
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
        $expenses = $this->managerRegistry->getRepository(VariousExpensive::class)->findAllByTrip($trip);
        $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

        /** @var VariousExpensive $expense */
        foreach ($expenses as $expense) {
            $calculatedPrice = $expense->getOriginalCurrency()?->getCode() !== 'EUR'
                ? $expense->getConvertedPrice()
                : $expense->getOriginalPrice();

            if ($trip->getCurrency() && $trip->getCurrency()->getCode() !== 'EUR') {
                $calculatedPrice = $this->converterService->convert(
                    $calculatedPrice,
                    $eurCurrency,
                    $trip->getCurrency(),
                    $expense->getConvertedAt() ?? $expense->getPurchaseDate()
                )['amount'];
            }

            $expense->setFinalPrice($calculatedPrice);
        }

        return $this->json($expenses);
    }

    #[Route('/get/{expensive}/form-data', name: 'getFormData', requirements: ['expensive' => '\d+'], methods: ['GET'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function get(?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => $this->translator->trans('expense.edit.not_found')], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'name' => $expensive->getName(),
            'description' => $expensive->getDescription(),
            'originalPrice' => $expensive->getOriginalPrice(),
            'originalCurrency' => $expensive->getOriginalCurrency(),
            'perPerson' => $expensive->isPerPerson()
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[Route('/edit/{expensive}', name: 'edit', requirements: ['expensive' => '\d+'], methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function create(
        Request           $request,
        ?Trip             $trip = null,
        ?VariousExpensive $expensive = new VariousExpensive()
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $selectedCurrency = isset($data['originalCurrency']) ? $this->managerRegistry->getRepository(Currency::class)
            ->findOneBy(['code' => $data['originalCurrency']]) : null;

        $dto = new VariousExpensiveRequestDTO($selectedCurrency);
        $dto = $this->dtoService->initDto($data, $dto);

        if (is_array($dto) && isset($dto['error'])) return $this->json(...$dto['error']);

        try {
            $eurCurrency = $this->managerRegistry->getRepository(Currency::class)->findOneBy(['code' => 'EUR']);

            $oldTotal = $this->budgetAlertService->getCategoryTotal($trip, 'various-expensive');

            $expensive->setPurchaseDate(new \DateTime());
            $expensive->setTrip($trip);
            $expensive->setPrice($dto->originalPrice);
            $expensive = $this->dtoService->mapToEntity($dto, $expensive);

            if ($eurCurrency !== $dto->originalCurrency && $expensive->getOriginalPrice() !== $dto->originalPrice) {
                $convertedDeposit = $this->converterService->convert($dto->originalPrice, $dto->originalCurrency, $eurCurrency);
                $expensive->setConvertedPrice($convertedDeposit['amount']);
                $expensive->setExchangeRate($convertedDeposit['rate']);
                $expensive->setConvertedAt(new \DateTimeImmutable());
            }

            $this->managerRegistry->getManager()->persist($expensive);
            $this->managerRegistry->getManager()->flush();

            $toastMessage = $this->budgetAlertService->checkAndNotify($trip, 'various-expensive', $oldTotal);

            if ($request->get('_route') === 'api_various_expensive_edit') {
                return $this->json([
                    'message' => $this->translator->trans('expense.updated'),
                    'warning' => $toastMessage
                ]);
            }

            return $this->json([
                'message' => $this->translator->trans('expense.created'),
                'warning' => $toastMessage
            ]);
        } catch (\Exception $e) {
            return $this->json(['message' => $this->translator->trans('expense.error.create')], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{expensive}', name: 'delete', requirements: ['expensive' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function delete(?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => $this->translator->trans('expense.delete.not_found')], Response::HTTP_NOT_FOUND);
        }

        $this->managerRegistry->getManager()->remove($expensive);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('expense.deleted')]);
    }

    #[Route('/update-reserved/{expensive}', name: 'update_reserved', requirements: ['expensive' => '\d+'], methods: ['PUT'])]
    #[IsGranted('edit_elements', subject: 'trip')]
    public function updateReserved(Request $request, ?Trip $trip = null, ?VariousExpensive $expensive = null): JsonResponse
    {
        if (!$expensive) {
            return $this->json(['message' => $this->translator->trans('expense.update.not_found')], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['reservedBy'])) {
            $expensive->setPayedBy($this->managerRegistry->getRepository(TripTraveler::class)->find($data['reservedBy']));
        } else {
            $expensive->setPayedBy(null);
        }

        $expensive->setPaid(!$expensive->isPaid());

        $this->managerRegistry->getManager()->persist($expensive);
        $this->managerRegistry->getManager()->flush();

        return $this->json(['message' => $this->translator->trans('expense.toggle_reserved')]);
    }
}