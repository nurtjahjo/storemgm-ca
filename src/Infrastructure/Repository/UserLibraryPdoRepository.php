<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;

class UserLibraryPdoRepository implements UserLibraryRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $table = 'storemgm_user_library'
    ) {}

    public function save(UserLibrary $item): void
    {
        $sql = "INSERT INTO {$this->table} 
            (id, user_id, product_id, source_order_id, access_type, started_at, expires_at, is_active)
            VALUES (:id, :uid, :pid, :oid, :type, :start, :expire, :active)
            ON DUPLICATE KEY UPDATE 
            expires_at = VALUES(expires_at), is_active = VALUES(is_active)";
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $item->getId(),
            ':uid' => $item->getUserId(),
            ':pid' => $item->getProductId(),
            ':oid' => $item->getSourceOrderId(),
            ':type' => $item->getAccessType(),
            ':start' => $item->getStartedAt()?->format('Y-m-d H:i:s'),
            ':expire' => $item->getExpiresAt()?->format('Y-m-d H:i:s'),
            ':active' => $item->isActive() ? 1 : 0
        ]);
    }

    public function findValidAccess(string $userId, string $productId): ?UserLibrary
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :uid 
                  AND product_id = :pid 
                  AND is_active = 1
                  AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId, ':pid' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return $this->mapRowToEntity($row);
    }

    public function findByUserId(string $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :uid 
                ORDER BY started_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    private function mapRowToEntity(array $row): UserLibrary
    {
        return new UserLibrary(
            $row['id'],
            $row['user_id'],
            $row['product_id'],
            $row['source_order_id'],
            $row['access_type'],
            new DateTime($row['started_at']),
            $row['expires_at'] ? new DateTime($row['expires_at']) : null,
            (bool)$row['is_active']
        );
    }
}
