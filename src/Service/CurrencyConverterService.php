<?php

namespace App\Service;

use App\Repository\ExchangeRateRepository;

class CurrencyConverterService
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository
    ) {}

    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        ?\DateTimeInterface $date = null
    ): array {
        if ($fromCurrency === $toCurrency) {
            return [
                'amount' => $amount,
                'rate' => 1.0,
                'date' => $date ?? new \DateTime(),
            ];
        }

        $exchangeRate = $date
            ? $this->exchangeRateRepository->getClosestRates($date)
            : $this->exchangeRateRepository->getLatestRates();

        if (!$exchangeRate) {
            throw new \Exception('Aucun taux de change disponible');
        }

        $rates = $exchangeRate->getRates();

        // EUR vers autre devise
        if ($fromCurrency === 'EUR') {
            if (!isset($rates[$toCurrency])) {
                throw new \Exception("Devise $toCurrency non disponible");
            }

            $rate = $rates[$toCurrency];
            $convertedAmount = $amount * $rate;
        }
        // Autre devise vers EUR
        elseif ($toCurrency === 'EUR') {
            if (!isset($rates[$fromCurrency])) {
                throw new \Exception("Devise $fromCurrency non disponible");
            }

            $rate = 1 / $rates[$fromCurrency];
            $convertedAmount = $amount * $rate;
        }
        // Entre deux devises (passage par EUR)
        else {
            if (!isset($rates[$fromCurrency]) || !isset($rates[$toCurrency])) {
                throw new \Exception('Une ou plusieurs devises non disponibles');
            }

            $amountInEUR = $amount / $rates[$fromCurrency];
            $convertedAmount = $amountInEUR * $rates[$toCurrency];
            $rate = $rates[$toCurrency] / $rates[$fromCurrency];
        }

        return [
            'amount' => round($convertedAmount, 2),
            'rate' => round($rate, 6),
            'date' => $exchangeRate->getDate(),
        ];
    }
}