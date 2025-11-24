<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

class CartItem
{
    public function __construct(
        private string $id,
        private string $cartId,
        private string $productId,
        private int $quantity = 1,
        private string $purchaseType = 'buy', // 'buy' or 'rent'
        private ?\DateTime $addedAt = null
    ) {}

    public function getId(): string { return $this->id; }
    public function getCartId(): string { return $this->cartId; }
    public function getProductId(): string { return $this->productId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPurchaseType(): string { return $this->purchaseType; }
    public function getAddedAt(): ?\DateTime { return $this->addedAt; }
}
