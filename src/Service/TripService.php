<?php

namespace App\Service;

use App\Entity\Trip;
use DateTime;

class TripService
{
    /**
     * Compte le nombre de jours à attendre ou passés pour le voyage
     * @param Trip $trip
     * @return bool|array|string
     */
    public function countDaysBeforeOrAfter(Trip $trip): bool|array|string
    {
        $departureDate = $trip->getDepartureDate();

        if (!$departureDate) return false;

        if ($departureDate > new DateTime()) {
            $diff = (new \DateTime('now'))->diff($departureDate);
            return [
                'before' => false,
                'days' => $diff->days
            ];
        }

        $returnDate = $trip->getReturnDate();
        if ($returnDate && $returnDate < new DateTime()) {
            $diff = (new \DateTime('now'))->diff($returnDate);
            return [
                'before' => true,
                'days' => $diff->days
            ];
        }

        return 'ongoing';
    }

    /**
     * Retourne le montant total des logement réservés
     * @param Trip $trip
     * @return float
     */
    public function getReservedAccommodationsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getAccommodations() as $accommodation) {
            if ($accommodation->isBooked()) {
                $price += $accommodation->getPrice();
                foreach ($accommodation->getAdditionalExpensive() as $item) $price += $item->getPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des logement non réservés
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedAccommodationsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getAccommodations() as $accommodation) {
            if (!$accommodation->isBooked()) {
                $price += $accommodation->getPrice();
                foreach ($accommodation->getAdditionalExpensive() as $item) $price += $item->getPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports réservés
     * @param Trip $trip
     * @return float
     */
    public function getReservedTransportsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getTransports() as $transport) {
            if (!$transport->isPaid()) continue;

            if ($transport->isPerPerson() && $transport->getType()->getName() !== 'Voiture') {
                $price += ($transport->getPrice() * $trip->getTravelers());
            } else if (!$transport->isPerPerson() && $transport->getType()->getName() === 'Voiture') {
                $price += ($transport->getEstimatedToll() + $transport->getEstimatedGasoline());
            } else {
                $price += $transport->getPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des transports non réservés
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedTransportsPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getTransports() as $transport) {
            if ($transport->isPaid()) continue;

            if ($transport->isPerPerson()) {
                $price += ($transport->getPrice() * $trip->getTravelers());
            } else {
                $price += $transport->getPrice();
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités réservées
     * @param Trip $trip
     * @return float
     */
    public function getReservedActivitiesPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getActivities() as $activity) {
            if ($activity->isBooked()) {
                if ($activity->isPerPerson()) {
                    $price += ($activity->getPrice() * $trip->getTravelers());
                } else {
                    $price += $activity->getPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * Retourne le montant total des activités non réservées
     * @param Trip $trip
     * @return float
     */
    public function getNonReservedActivitiesPrice(Trip $trip): float
    {
        $price = 0;
        foreach ($trip->getActivities() as $activity) {
            if (!$activity->isBooked()) {
                if ($activity->isPerPerson()) {
                    $price += ($activity->getPrice() * $trip->getTravelers());
                } else {
                    $price += $activity->getPrice();
                }
            }
        }

        return round($price, 2);
    }

    /**
     * TODO
     * Retourne le montant des dépenses déjà payées et celles restantes à payer
     * @param Trip $trip
     * @return array
     */
    public function getBudget(Trip $trip): array
    {
        $reservedPrices = [
            'accommodations' => $this->getReservedAccommodationsPrice($trip),
            'transports' => $this->getReservedTransportsPrice($trip),
            'activities' => $this->getReservedActivitiesPrice($trip),
        ];

        $nonReservedPrices = [
            'accommodations' => $this->getNonReservedAccommodationsPrice($trip),
            'transports' => $this->getNonReservedTransportsPrice($trip),
            'activities' => $this->getNonReservedActivitiesPrice($trip),
        ];

        $totalReserved = round(array_sum($reservedPrices), 2);
        $totalNonReserved = round(array_sum($nonReservedPrices), 2);

        return [
            'toPay' => $totalNonReserved,
            'paid' => $totalReserved,
            'total' => round($totalNonReserved + $totalReserved, 2),
            'details' => [
                'reserved' => $reservedPrices,
                'nonReserved' => $nonReservedPrices,
            ],
        ];
    }
}