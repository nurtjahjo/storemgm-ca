<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;
use Nurtjahjo\StoremgmCA\Domain\Repository\CustomerProfileRepositoryInterface;

class CustomerProfilePdoRepository implements CustomerProfileRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $table = 'storemgm_customer_profiles'
    ) {}

    public function findByUserId(string $userId): ?CustomerProfile
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new CustomerProfile(
            $row['user_id'],
            $row['billing_address'],
            $row['shipping_address'],
            new DateTime($row['created_at']),
            new DateTime($row['updated_at'])
        );
    }

    public function save(CustomerProfile $profile): void
    {
        $sql = "INSERT INTO {$this->table} (user_id, billing_address, shipping_address, created_at, updated_at)
                VALUES (:uid, :bill, :ship, :created, :updated)
                ON DUPLICATE KEY UPDATE
                billing_address = VALUES(billing_address),
                shipping_address = VALUES(shipping_address),
                updated_at = VALUES(updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $profile->getUserId(),
            ':bill' => $profile->getBillingAddress(),
            ':ship' => $profile->getShippingAddress(),
            ':created' => (new DateTime())->format('Y-m-d H:i:s'),
            ':updated' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
