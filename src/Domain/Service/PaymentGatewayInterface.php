<?php

namespace Nurtjahjo\StoremgmCA\Domain\Service;

use Nurtjahjo\StoremgmCA\Domain\Entity\Order;

interface PaymentGatewayInterface
{
    /**
     * Membuat transaksi pembayaran.
     * @return array Berisi 'redirect_url' atau 'token' pembayaran.
     */
    public function createTransaction(Order $order): array;
}
