<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;
use Ramsey\Uuid\Uuid;
use DateTime;
use RuntimeException;

class ProcessPaymentCallbackUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepo,
        private UserLibraryRepositoryInterface $libraryRepo,
        private ProductRepositoryInterface $productRepo,
        private LoggerInterface $logger
    ) {}

    /**
     * Memproses notifikasi pembayaran.
     * @param array $data Data webhook dari payment gateway (order_id, transaction_status, dll)
     */
    public function execute(array $data): void
    {
        // 1. Ekstrak Data Penting (Sesuaikan dengan format Midtrans/Gateway Anda)
        // Asumsi: $data['order_id'] dan $data['transaction_status']
        $orderId = $data['order_id'] ?? null;
        $status = $data['transaction_status'] ?? null;

        if (!$orderId || !$status) {
            throw new RuntimeException("Invalid callback data.");
        }

        // 2. Cari Order
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            $this->logger->log("Callback received for unknown Order ID: $orderId", 'ERROR');
            throw new RuntimeException("Order not found.");
        }

        // 3. Tentukan Status Order Baru (Simplifikasi logika Midtrans)
        // settlement/capture -> sukses
        // deny/expire/cancel -> gagal
        $isSuccess = in_array($status, ['capture', 'settlement']);
        $isFailed = in_array($status, ['deny', 'expire', 'cancel']);

        if ($isSuccess && $order->getStatus() !== 'completed') {
            // --- PROSES SUKSES: GRANT ACCESS ---
            
            $this->grantAccessToUser($order);
            
            // Update Status Order
            $order->setStatus('completed'); // atau 'paid'
            $this->orderRepo->save($order);
            
            $this->logger->log("Order $orderId completed. Access granted.", 'INFO');

        } elseif ($isFailed) {
            $order->setStatus('failed');
            $this->orderRepo->save($order);
            $this->logger->log("Order $orderId marked as failed.", 'INFO');
        }
    }

    private function grantAccessToUser($order): void
    {
        foreach ($order->getItems() as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            if (!$product) continue;

            $purchaseType = $item->getPurchaseType(); // 'buy' or 'rent'
            
            $expiresAt = null;
            $accessType = 'owned';

            // Logika Sewa
            if ($purchaseType === 'rent') {
                $accessType = 'rented';
                $duration = $product->getRentalDurationDays() ?? 30; // Default 30 hari jika null
                $expiresAt = (new DateTime())->modify("+$duration days");
            }

            // Buat Entry di User Library
            $libraryItem = new UserLibrary(
                Uuid::uuid4()->toString(),
                $order->getUserId(),
                $item->getProductId(),
                $order->getId(),
                $accessType,
                new DateTime(), // started_at
                $expiresAt,
                true // is_active
            );

            $this->libraryRepo->save($libraryItem);
        }
    }
}
