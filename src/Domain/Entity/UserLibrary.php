<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use DateTime;

class UserLibrary
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $productId,
        private string $sourceOrderId,
        private string $accessType, // 'owned', 'rented'
        private ?DateTime $startedAt,
        private ?DateTime $expiresAt,
        private bool $isActive
    ) {}

    public function hasAccess(): bool
    {
        if (!$this->isActive) return false;
        
        // Jika owned (beli putus), expiresAt NULL -> Akses selamanya
        if ($this->accessType === 'owned') return true;

        // Jika rented, cek tanggal
        if ($this->expiresAt) {
            return new DateTime() < $this->expiresAt;
        }

        return false;
    }

    // Getters...
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getProductId(): string { return $this->productId; }
    public function getExpiresAt(): ?DateTime { return $this->expiresAt; }
}
