<?php

namespace Nurtjahjo\StoremgmCA\Application\DTO;

class CartDetails
{
    /**
     * @param string $cartId
     * @param CartItemDetail[] $items
     * @param float $grandTotal
     * @param string $currency
     */
    public function __construct(
        public string $cartId,
        public array $items,
        public float $grandTotal,
        public string $currency
    ) {}
}
