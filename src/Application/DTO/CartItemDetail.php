<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Application\DTO;

class CartItemDetail
{
    public function __construct(
        public string $productId,
        public string $title,
        public string $coverImage,
        public int $quantity,
        public float $pricePerUnit,
        public float $totalPrice,
        public string $purchaseType, // 'buy' or 'rent'
        public string $type, // 'ebook' or 'audiobook'
        public ?int $rentalDurationDays // NULL jika purchaseType = 'buy'
    ) {}
}
