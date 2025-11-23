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
    
    // Method untuk membersihkan keranjang (misal setelah checkout)
    public function delete(string $cartId): void;
}
