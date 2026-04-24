<?php

namespace App\Service;

use App\Entity\Currency;
use App\Repository\ExchangeRateRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrencyConverterService
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly TranslatorInterface    $translator,
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

        $exchangeRate = null;
        if ($date) $exchangeRate = $this->exchangeRateRepository->getClosestRates($date);
        if (!$exchangeRate) $exchangeRate = $this->exchangeRateRepository->getLatestRates();

        if (!$exchangeRate) {
            throw new \Exception($this->translator->trans('currency.exchange_rate_unavailable'));
        }

        $rates = $exchangeRate->getRates();

        // EUR vers autre devise
        if ($fromCurrency->getCode() === 'EUR') {
            if (!isset($rates[$toCurrency->getCode()])) {
                throw new \Exception($this->translator->trans('currency.code_unavailable', ['%code%' => $toCurrency->getCode()]));
            }

            $rate = $rates[$toCurrency->getCode()];
            $convertedAmount = $amount * $rate;
        }
        // Autre devise vers EUR
        elseif ($toCurrency->getCode() === 'EUR') {
            if (!isset($rates[$fromCurrency->getCode()])) {
                throw new \Exception($this->translator->trans('currency.code_unavailable', ['%code%' => $fromCurrency->getCode()]));
            }

            $rate = 1 / $rates[$fromCurrency->getCode()];
            $convertedAmount = $amount * $rate;
        }
        // Entre deux devises (passage par EUR)
        else {
            if (!isset($rates[$fromCurrency->getCode()]) || !isset($rates[$toCurrency->getCode()])) {
                throw new \Exception($this->translator->trans('currency.multiple_unavailable'));
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