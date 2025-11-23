<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Service;

use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class MidtransPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function createTransaction(Order $order): array
    {
        // Di Production, kode ini akan menggunakan cURL/Guzzle ke API Midtrans.
        // Di sini kita simulasikan saja.
        
        $this->logger->log("Initiating Payment for Order: " . $order->getId(), 'INFO');
        $this->logger->log("Amount: " . $order->getTotalPriceIdr() . " IDR");

        // Simulasi response sukses dari Midtrans Snap
        return [
            'token' => 'dummy-snap-token-' . uniqid(),
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . uniqid(),
            'transaction_id' => 'TRX-' . $order->getId() // ID Transaksi dari Payment Gateway
        ];
    }
}
