<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use DateTime;

class Wishlist
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $productId,
        private ?DateTime $createdAt = null
    ) {}

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getProductId(): string { return $this->productId; }
    public function getCreatedAt(): ?DateTime { return $this->createdAt; }
}
