<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Domain\Service;

use Nurtjahjo\StoremgmCA\Domain\Entity\Order;

interface PaymentGatewayInterface
{
    public function createTransaction(Order $order): array;
    
    /**
     * Memvalidasi notifikasi webhook untuk memastikan asalnya benar.
     * @param array $notificationData Data JSON dari webhook
     * @param string $serverKey Kunci rahasia server
     * @return bool True jika valid
     */
    public function validateSignature(array $notificationData, string $serverKey): bool;
}
