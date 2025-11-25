<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface; // NEW Dependency
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Exception\InvalidSignatureException;
use Ramsey\Uuid\Uuid;
use DateTime;
use RuntimeException;

class ProcessPaymentCallbackUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepo,
        private UserLibraryRepositoryInterface $libraryRepo,
        private ProductRepositoryInterface $productRepo,
        private PaymentGatewayInterface $paymentGateway, // NEW
        private LoggerInterface $logger,
        private string $serverKey // Injected via Bootstrap
    ) {}

    public function execute(array $data): void
    {
        // 1. SECURITY: Validate Signature
        if (!$this->paymentGateway->validateSignature($data, $this->serverKey)) {
            $this->logger->log("Invalid Webhook Signature detected.", 'CRITICAL');
            throw new InvalidSignatureException("Security check failed.");
        }

        $orderId = $data['order_id'] ?? null;
        $status = $data['transaction_status'] ?? null;

        if (!$orderId || !$status) {
            throw new RuntimeException("Invalid callback data structure.");
        }

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            $this->logger->log("Callback for unknown Order ID: $orderId", 'ERROR');
            throw new RuntimeException("Order not found.");
        }

        $isSuccess = in_array($status, ['capture', 'settlement']);
        $isFailed = in_array($status, ['deny', 'expire', 'cancel']);

        if ($isSuccess && $order->getStatus() !== 'completed') {
            $this->grantAccessToUser($order);
            
            $order->setStatus('completed');
            $this->orderRepo->save($order);
            
            $this->logger->log("Order $orderId completed successfully.", 'INFO');

        } elseif ($isFailed) {
            $order->setStatus('failed');
            $this->orderRepo->save($order);
            $this->logger->log("Order $orderId failed.", 'INFO');
        }
    }

    private function grantAccessToUser(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            if (!$product) continue;

            $purchaseType = $item->getPurchaseType();
            $expiresAt = null;
            $accessType = 'owned';

            if ($purchaseType === 'rent') {
                $accessType = 'rented';
                $duration = $product->getRentalDurationDays() ?? 30;
                $expiresAt = (new DateTime())->modify("+$duration days");
            }

            $libraryItem = new UserLibrary(
                Uuid::uuid4()->toString(),
                $order->getUserId(),
                $item->getProductId(),
                $order->getId(),
                $accessType,
                new DateTime(),
                $expiresAt,
                true
            );

            $this->libraryRepo->save($libraryItem);
        }
    }
}
