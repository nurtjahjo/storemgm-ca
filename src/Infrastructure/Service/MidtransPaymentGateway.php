<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Infrastructure\Service;

use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class MidtransPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function createTransaction(Order $order): array
    {
        $this->logger->log("Initiating Payment for Order: " . $order->getId());
        
        return [
            'token' => 'dummy-snap-token-' . uniqid(),
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . uniqid(),
            'transaction_id' => 'TRX-' . $order->getId()
        ];
    }

    public function validateSignature(array $data, string $serverKey): bool
    {
        // Di Production (Midtrans):
        // Signature = SHA512(order_id + status_code + gross_amount + ServerKey)
        
        // Di Mock/Local:
        // Kita simulasikan validasi sederhana. 
        // Misalnya, kita anggap valid jika ada field 'signature_key' (meskipun isinya dummy)
        // Atau untuk simulasi Bruno yang mudah, kita return true dulu,
        // TAPI kita log bahwa validasi terjadi.
        
        $this->logger->log("Validating Webhook Signature...", 'INFO');
        
        // UNCOMMENT INI UNTUK SIMULASI STRICT:
        /*
        if (!isset($data['signature_key'])) {
             return false;
        }
        // logic hash checking here
        */

        return true; // Bypass untuk dev lokal agar mudah dites via Bruno
    }
}
