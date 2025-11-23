<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class Order
{
    /** @var OrderItem[] */
    private array $items = [];

    public function __construct(
        private string $id,
        private string $userId,
        private Money $totalPriceUsd,
        private ?float $totalPriceIdr = null,
        private ?float $exchangeRate = null,
        private string $status = 'pending', // pending, completed, failed
        private ?string $paymentGatewayTransactionId = null,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getTotalPriceUsd(): Money { return $this->totalPriceUsd; }
    public function getTotalPriceIdr(): ?float { return $this->totalPriceIdr; }
    public function getExchangeRate(): ?float { return $this->exchangeRate; }
    public function getStatus(): string { return $this->status; }
    public function getPaymentGatewayTransactionId(): ?string { return $this->paymentGatewayTransactionId; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }

    public function setPaymentGatewayTransactionId(string $id): void 
    {
        $this->paymentGatewayTransactionId = $id;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /** @return OrderItem[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
