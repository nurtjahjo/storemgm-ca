<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class OrderItem
{
    public function __construct(
        private string $id,
        private string $orderId,
        private string $productId,
        private int $quantity,
        private Money $priceAtPurchase,
        private string $purchaseType = 'buy' // 'buy' or 'rent'
    ) {}

    public function getId(): string { return $this->id; }
    public function getOrderId(): string { return $this->orderId; }
    public function getProductId(): string { return $this->productId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPriceAtPurchase(): Money { return $this->priceAtPurchase; }
    public function getPurchaseType(): string { return $this->purchaseType; }
}
