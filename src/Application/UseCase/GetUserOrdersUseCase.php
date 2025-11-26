<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;

class GetUserOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepo,
        private ProductRepositoryInterface $productRepo
    ) {}

    public function execute(string $userId): array
    {
        $orders = $this->orderRepo->findByUserId($userId);
        
        // Transform Entity ke DTO/Array yang lebih ramah API (termasuk nama produk)
        // Karena OrderItem hanya simpan ID Produk, kita perlu lookup nama produk
        
        $result = [];
        foreach ($orders as $order) {
            $itemsData = [];
            foreach ($order->getItems() as $item) {
                $product = $this->productRepo->findById($item->getProductId());
                $productName = $product ? $product->getTitle() : 'Unknown Product';
                
                $itemsData[] = [
                    'product_id' => $item->getProductId(),
                    'product_name' => $productName,
                    'quantity' => $item->getQuantity(),
                    'price_at_purchase' => $item->getPriceAtPurchase()->getAmount(),
                    'purchase_type' => $item->getPurchaseType()
                ];
            }

            $result[] = [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'total_usd' => $order->getTotalPriceUsd()->getAmount(),
                'total_idr' => $order->getTotalPriceIdr(),
                'created_at' => $order->getCreatedAt()->format('c'), // ISO 8601
                'items' => $itemsData
            ];
        }
        
        return $result;
    }
}
