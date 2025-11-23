<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Entity\OrderItem;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class OrderPdoRepository implements OrderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $orderTable = 'storemgm_orders',
        private string $itemTable = 'storemgm_order_items'
    ) {}

    public function save(Order $order): void
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Simpan Header Order
            $sqlOrder = "INSERT INTO {$this->orderTable} 
                (id, user_id, total_price_usd, total_price_idr, exchange_rate, status, payment_gateway_transaction_id, created_at, updated_at)
                VALUES (:id, :user_id, :total_usd, :total_idr, :rate, :status, :pg_trx_id, :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                payment_gateway_transaction_id = VALUES(payment_gateway_transaction_id),
                updated_at = VALUES(updated_at)";

            $stmt = $this->pdo->prepare($sqlOrder);
            $stmt->execute([
                ':id' => $order->getId(),
                ':user_id' => $order->getUserId(),
                ':total_usd' => $order->getTotalPriceUsd()->getAmount(),
                ':total_idr' => $order->getTotalPriceIdr(),
                ':rate' => $order->getExchangeRate(),
                ':status' => $order->getStatus(),
                ':pg_trx_id' => $order->getPaymentGatewayTransactionId(),
                ':created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ':updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            // 2. Simpan Items (Hanya jika Insert baru, biasanya items tidak diupdate setelah order dibuat)
            // Untuk sederhana, kita cek apakah items sudah ada atau belum.
            // Dalam use case 'Checkout', order pasti baru.
            
            $stmtItem = $this->pdo->prepare("INSERT IGNORE INTO {$this->itemTable} 
                (id, order_id, product_id, quantity, price_usd_at_purchase)
                VALUES (:id, :order_id, :product_id, :qty, :price)");

            foreach ($order->getItems() as $item) {
                $stmtItem->execute([
                    ':id' => $item->getId(),
                    ':order_id' => $order->getId(),
                    ':product_id' => $item->getProductId(),
                    ':qty' => $item->getQuantity(),
                    ':price' => $item->getPriceAtPurchase()->getAmount()
                ]);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findById(string $id): ?Order
    {
        // Implementasi standar pengambilan data + hydration items (mirip CartPdoRepository)
        // ... (Akan diimplementasikan jika diperlukan untuk callback payment)
        return null; 
    }

    public function findByUserId(string $userId): array
    {
        // Implementasi untuk history pesanan
        return [];
    }
}
