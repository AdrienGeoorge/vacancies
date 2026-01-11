<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\ExchangeRateRepository;

class CurrencyConverterService
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository
    ) {}

    /**
     * @throws \Exception
     */
    public function convert(
        float $amount,
        Currency $fromCurrency,
        Currency $toCurrency,
        ?\DateTimeInterface $date = null
    ): array {
        if ($fromCurrency === $toCurrency) {
            return [
                'amount' => $amount,
                'rate' => 1.0,
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
        if ($fromCurrency->getCode() === 'EUR') {
            if (!isset($rates[$toCurrency->getCode()])) {
                throw new \Exception("Devise {$toCurrency->getCode()} non disponible");
            }

            $rate = $rates[$toCurrency->getCode()];
            $convertedAmount = $amount * $rate;
        }
        // Autre devise vers EUR
        elseif ($toCurrency->getCode() === 'EUR') {
            if (!isset($rates[$fromCurrency->getCode()])) {
                throw new \Exception("Devise {$fromCurrency->getCode()} non disponible");
            }

            $rate = 1 / $rates[$fromCurrency->getCode()];
            $convertedAmount = $amount * $rate;
        }
        // Entre deux devises (passage par EUR)
        else {
            if (!isset($rates[$fromCurrency->getCode()]) || !isset($rates[$toCurrency->getCode()])) {
                throw new \Exception('Une ou plusieurs devises non disponibles');
            }

            $amountInEUR = $amount / $rates[$fromCurrency->getCode()];
            $convertedAmount = $amountInEUR * $rates[$toCurrency->getCode()];
            $rate = $rates[$toCurrency->getCode()] / $rates[$fromCurrency->getCode()];
        }

        return [
            'amount' => round($convertedAmount, 2),
            'rate' => round($rate, 6)
        ];
    }
}