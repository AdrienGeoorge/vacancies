<?php

namespace App\Service;

use App\Entity\Trip;
use App\Repository\TripBudgetRepository;
use App\Repository\UserNotificationsRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class BudgetAlertService
{
    private const CATEGORY_KEYS = [
        'accommodations'   => 'budget.category.accommodations',
        'transports'       => 'budget.category.transports',
        'activities'       => 'budget.category.activities',
        'various-expensive' => 'budget.category.various_expensive',
        'on-site'          => 'budget.category.on_site',
    ];

    public function __construct(
        private readonly ManagerRegistry             $managerRegistry,
        private readonly TripService                 $tripService,
        private readonly TripBudgetRepository        $budgetRepository,
        private readonly UserNotificationsRepository $notificationsRepository,
        private readonly TranslatorInterface         $translator,
    )
    {
    }

    /**
     * Retourne le total des dépenses pour une catégorie donnée.
     */
    public function getCategoryTotal(Trip $trip, string $category): float
    {
        return match ($category) {
            'accommodations' => $this->tripService->getReservedAccommodationsPrice($trip)
                + $this->tripService->getNonReservedAccommodationsPrice($trip),
            'transports' => $this->tripService->getReservedTransportsPrice($trip)
                + $this->tripService->getNonReservedTransportsPrice($trip),
            'activities' => $this->tripService->getReservedActivitiesPrice($trip)
                + $this->tripService->getNonReservedActivitiesPrice($trip),
            'various-expensive' => $this->tripService->getReservedVariousExpensivePrice($trip)
                + $this->tripService->getNonReservedVariousExpensivePrice($trip),
            'on-site' => $this->tripService->getOnSiteExpensePrice($trip),
            default => 0.0,
        };
    }

    /**
     * Compare l'ancien total (avant flush) avec le nouveau total (récupéré après un refresh de l'entité Trip)
     * et envoie une notification à tous les membres du voyage si un seuil (80 % ou 100 %) est franchi.
     */
    public function checkAndNotify(Trip $trip, string $category, float $oldTotal): ?string
    {
        $budget = $this->budgetRepository->findOneBy(['trip' => $trip, 'category' => $category]);
        if (!$budget || !$budget->getAmount() || $budget->getAmount() <= 0) {
            return null;
        }

        $budgetAmount = (float)$budget->getAmount();

        // Recharge les collections depuis la BDD pour obtenir le total à jour après flush
        $this->managerRegistry->getManager()->refresh($trip);
        $newTotal = $this->getCategoryTotal($trip, $category);

        $oldPercent = ($oldTotal / $budgetAmount) * 100;
        $newPercent = ($newTotal / $budgetAmount) * 100;

        $labelKey = self::CATEGORY_KEYS[$category] ?? $category;
        $label = $this->translator->trans($labelKey);
        $messageNotif = null;
        $messageToast = null;

        if ($oldPercent < 100 && $newPercent >= 100) {
            $actualPct = (int)round($newPercent);
            $messageNotif = $this->translator->trans('budget.alert.exceeded', ['%label%' => $label, '%trip%' => $trip->getName(), '%pct%' => $actualPct]);
            $messageToast = $this->translator->trans('budget.alert.exceeded_toast', ['%pct%' => $actualPct]);
        } elseif ($oldPercent < 80 && $newPercent >= 80) {
            $messageNotif = $this->translator->trans('budget.alert.warning', ['%label%' => $label, '%trip%' => $trip->getName()]);
            $messageToast = $this->translator->trans('budget.alert.warning_toast');
        }

        if ($messageNotif === null) {
            return null;
        }

        foreach ($trip->getTripTravelers() as $traveler) {
            $user = $traveler->getInvited();
            if ($user !== null) {
                $this->notificationsRepository->sendNotification(
                    $user,
                    $messageNotif,
                    null,
                    '/trips/' . $trip->getId()
                );
            }
        }

        return $messageToast;
    }
}
