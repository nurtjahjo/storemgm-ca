<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;

interface CartRepositoryInterface
{
    public function findByUserId(string $userId): ?Cart;
    public function findByGuestId(string $guestCartId): ?Cart;
    
    public function save(Cart $cart): void;
    public function addItem(CartItem $item): void;
    
    public function delete(string $cartId): void;

    // Method baru untuk Merge
    public function transferOwnership(string $cartId, string $newUserId): void;
    public function moveItemToCart(string $itemId, string $targetCartId): void;
}
