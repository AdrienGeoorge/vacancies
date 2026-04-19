<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\TripBudget;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/trips/{trip}/budget', name: 'api_budget_', requirements: ['trip' => '\d+'])]
class BudgetController extends AbstractController
{
    private const VALID_CATEGORIES = [
        'accommodations',
        'transports',
        'activities',
        'various-expensive',
        'on-site',
    ];

    public function __construct(
        private readonly ManagerRegistry     $managerRegistry,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    #[IsGranted('view', subject: 'trip', message: 'trip.access.view_denied', statusCode: 403)]
    public function get(?Trip $trip = null): JsonResponse
    {
        $budgets = $this->managerRegistry->getRepository(TripBudget::class)->findBy(['trip' => $trip]);

        $result = [];
        foreach ($budgets as $budget) {
            $result[$budget->getCategory()] = $budget->getAmount();
        }

        return $this->json($result);
    }

    #[Route('', name: 'save', methods: ['POST'])]
    #[IsGranted('edit_elements', subject: 'trip', message: 'trip.access.edit_elements_denied', statusCode: 403)]
    public function save(Request $request, ?Trip $trip = null): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => $this->translator->trans('budget.invalid_data')], Response::HTTP_BAD_REQUEST);
        }

        foreach ($data as $category => $amount) {
            if (!in_array($category, self::VALID_CATEGORIES, true)) {
                continue;
            }

            $budget = $this->managerRegistry->getRepository(TripBudget::class)
                ->findOneBy(['trip' => $trip, 'category' => $category]);

            if (!$budget) {
                $budget = (new TripBudget())
                    ->setTrip($trip)
                    ->setCategory($category);
            }

            $budget->setAmount($amount !== null && $amount !== '' ? (float)$amount : null);
            $this->managerRegistry->getManager()->persist($budget);
        }

        $this->managerRegistry->getManager()->flush();

        return $this->json([]);
    }
}
