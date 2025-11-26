<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Service;

use Nurtjahjo\StoremgmCA\Domain\Service\CurrencyConverterInterface;

class ApiCurrencyConverter implements CurrencyConverterInterface
{
    // Rate statis untuk development. Nanti bisa diganti call ke API Fixer.io/bi.go.id
    private const RATE_USD_IDR = 16000.0; 

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $amount * $rate;
    }

    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === 'USD' && $toCurrency === 'IDR') {
            return self::RATE_USD_IDR;
        }
        if ($fromCurrency === 'IDR' && $toCurrency === 'USD') {
            return 1 / self::RATE_USD_IDR;
        }
        return 1.0; // Mata uang sama
    }
}
