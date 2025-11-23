<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;

class CartPdoRepository implements CartRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $cartTable = 'storemgm_carts',
        private string $itemTable = 'storemgm_cart_items'
    ) {}

    public function findByUserId(string $userId): ?Cart
    {
        return $this->findBy('user_id', $userId);
    }

    public function findByGuestId(string $guestCartId): ?Cart
    {
        return $this->findBy('guest_cart_id', $guestCartId);
    }

    private function findBy(string $field, string $value): ?Cart
    {
        // 1. Ambil Header Cart
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->cartTable} WHERE {$field} = :val LIMIT 1");
        $stmt->execute([':val' => $value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $cart = $this->mapRowToCart($row);

        // 2. Ambil Items (Hydration)
        $stmtItems = $this->pdo->prepare("SELECT * FROM {$this->itemTable} WHERE cart_id = :cart_id");
        $stmtItems->execute([':cart_id' => $cart->getId()]);
        $itemRows = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($itemRows as $itemRow) {
            $items[] = $this->mapRowToCartItem($itemRow);
        }
        
        $cart->setItems($items);

        return $cart;
    }

    public function save(Cart $cart): void
    {
        $sql = "INSERT INTO {$this->cartTable} (id, user_id, guest_cart_id, created_at, updated_at)
                VALUES (:id, :user_id, :guest_cart_id, :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                guest_cart_id = VALUES(guest_cart_id),
                updated_at = VALUES(updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $cart->getId(),
            ':user_id' => $cart->getUserId(),
            ':guest_cart_id' => $cart->getGuestCartId(),
            ':created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ':updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function addItem(CartItem $item): void
    {
        $sql = "INSERT INTO {$this->itemTable} (id, cart_id, product_id, quantity, added_at)
                VALUES (:id, :cart_id, :product_id, :quantity, :added_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $item->getId(),
            ':cart_id' => $item->getCartId(),
            ':product_id' => $item->getProductId(),
            ':quantity' => $item->getQuantity(),
            ':added_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(string $cartId): void
    {
        // Hapus items dulu (meskipun CASCADE biasanya menangani ini, lebih aman eksplisit)
        $stmt = $this->pdo->prepare("DELETE FROM {$this->cartTable} WHERE id = :id");
        $stmt->execute([':id' => $cartId]);
    }

    private function mapRowToCart(array $row): Cart
    {
        return new Cart(
            $row['id'],
            $row['user_id'],
            $row['guest_cart_id'],
            new DateTime($row['created_at']),
            new DateTime($row['updated_at'])
        );
    }

    private function mapRowToCartItem(array $row): CartItem
    {
        return new CartItem(
            $row['id'],
            $row['cart_id'],
            $row['product_id'],
            (int) $row['quantity'],
            new DateTime($row['added_at'])
        );
    }
}
