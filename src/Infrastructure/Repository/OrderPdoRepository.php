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
                ':created_at' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                ':updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ]);

            $stmtItem = $this->pdo->prepare("INSERT IGNORE INTO {$this->itemTable} 
                (id, order_id, product_id, quantity, price_usd_at_purchase, purchase_type)
                VALUES (:id, :order_id, :product_id, :qty, :price, :ptype)");

            foreach ($order->getItems() as $item) {
                $stmtItem->execute([
                    ':id' => $item->getId(),
                    ':order_id' => $order->getId(),
                    ':product_id' => $item->getProductId(),
                    ':qty' => $item->getQuantity(),
                    ':price' => $item->getPriceAtPurchase()->getAmount(),
                    ':ptype' => $item->getPurchaseType()
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
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->orderTable} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return $this->hydrateOrder($row);
    }

    public function findByUserId(string $userId): array
    {
        // Ambil semua order milik user, urutkan dari yang terbaru
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->orderTable} WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = $this->hydrateOrder($row);
        }
        return $orders;
    }
    
    /**
     * Helper untuk mengubah row database + items menjadi Entity Order lengkap
     */
    private function hydrateOrder(array $row): Order
    {
        // Ambil Items
        $stmtItems = $this->pdo->prepare("SELECT * FROM {$this->itemTable} WHERE order_id = :order_id");
        $stmtItems->execute([':order_id' => $row['id']]);
        $rowsItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rowsItems as $r) {
            $items[] = new OrderItem(
                $r['id'],
                $r['order_id'],
                $r['product_id'],
                (int)$r['quantity'],
                new Money((float)$r['price_usd_at_purchase'], 'USD'),
                $r['purchase_type']
            );
        }

        $order = new Order(
            $row['id'],
            $row['user_id'],
            new Money((float)$row['total_price_usd'], 'USD'),
            $row['total_price_idr'] ? (float)$row['total_price_idr'] : null,
            $row['exchange_rate'] ? (float)$row['exchange_rate'] : null,
            $row['status'],
            $row['payment_gateway_transaction_id'],
            new DateTime($row['created_at']),
            new DateTime($row['updated_at'])
        );
        $order->setItems($items);

        return $order;
    }
}
