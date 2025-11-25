<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

class CustomerProfile
{
    public function __construct(
        private string $userId,
        private ?string $billingAddress,
        private ?string $shippingAddress,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getUserId(): string { return $this->userId; }
    public function getBillingAddress(): ?string { return $this->billingAddress; }
    public function getShippingAddress(): ?string { return $this->shippingAddress; }
    
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'billing_address' => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }
}
