<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\Wishlist;

interface WishlistRepositoryInterface
{
    public function save(Wishlist $wishlist): void;
    public function remove(string $userId, string $productId): void;
    public function exists(string $userId, string $productId): bool;
    
    /**
     * @return Wishlist[]
     */
    public function findByUserId(string $userId): array;
}
