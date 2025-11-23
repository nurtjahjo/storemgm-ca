<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

class Cart
{
    /** @var CartItem[] */
    private array $items = [];

    public function __construct(
        private string $id,
        private ?string $userId,
        private ?string $guestCartId,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): string { return $this->id; }
    public function getUserId(): ?string { return $this->userId; }
    public function getGuestCartId(): ?string { return $this->guestCartId; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function hasProduct(string $productId): bool
    {
        foreach ($this->items as $item) {
            if ($item->getProductId() === $productId) {
                return true;
            }
        }
        return false;
    }
}
