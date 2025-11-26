<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Entity\Wishlist;
use Nurtjahjo\StoremgmCA\Domain\Repository\WishlistRepositoryInterface;

class WishlistPdoRepository implements WishlistRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $table = 'storemgm_wishlists'
    ) {}

    public function save(Wishlist $wishlist): void
    {
        // Gunakan INSERT IGNORE untuk idempotency (jika sudah ada, abaikan error)
        $sql = "INSERT IGNORE INTO {$this->table} (id, user_id, product_id, created_at) 
                VALUES (:id, :uid, :pid, :created)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $wishlist->getId(),
            ':uid' => $wishlist->getUserId(),
            ':pid' => $wishlist->getProductId(),
            ':created' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function remove(string $userId, string $productId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    }

    public function exists(string $userId, string $productId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(1) FROM {$this->table} WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findByUserId(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->execute([':uid' => $userId]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Wishlist(
                $row['id'],
                $row['user_id'],
                $row['product_id'],
                new DateTime($row['created_at'])
            );
        }
        return $results;
    }
}
