<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(string $id): ?Order;
    public function findByUserId(string $userId): array;
}
