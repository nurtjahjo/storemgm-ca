<?php

namespace Nurtjahjo\StoremgmCA\Domain\Service;

interface CurrencyConverterInterface
{
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float;
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float;
}
