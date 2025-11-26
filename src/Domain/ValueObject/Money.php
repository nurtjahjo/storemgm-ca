<?php

namespace Nurtjahjo\StoremgmCA\Domain\ValueObject;

use InvalidArgumentException;

class Money
{
    /**
     * @param float $amount Jumlah uang (misal: 10.50)
     * @param string $currency Kode mata uang (ISO 4217, misal: 'USD', 'IDR')
     */
    public function __construct(
        private float $amount,
        private string $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount cannot be negative.");
        }
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be a 3-letter ISO code.");
        }
        $this->currency = strtoupper($currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        // Format sederhana, bisa ditingkatkan nanti
        return $this->currency . ' ' . number_format($this->amount, 2);
    }
    
    // Helper untuk konversi dari database (biasanya disimpan sebagai decimal)
    public static function fromDecimal(string|float $amount, string $currency = 'USD'): self
    {
        return new self((float)$amount, $currency);
    }
}
