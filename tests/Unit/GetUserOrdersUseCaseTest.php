<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserOrdersUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Entity\OrderItem;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class GetUserOrdersUseCaseTest extends TestCase
{
    private $orderRepo;
    private $productRepo;
    private $useCase;

    protected function setUp(): void
    {
        $this->orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        
        $this->useCase = new GetUserOrdersUseCase(
            $this->orderRepo,
            $this->productRepo
        );
    }

    public function test_returns_formatted_order_list_with_product_names()
    {
        $userId = 'user-1';
        
        // 1. Setup Order Item
        $item = new OrderItem('item-1', 'ord-1', 'prod-A', 1, new Money(10, 'USD'), 'buy');
        
        // 2. Setup Order
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('ord-1');
        $order->method('getStatus')->willReturn('completed');
        $order->method('getTotalPriceUsd')->willReturn(new Money(10, 'USD'));
        $order->method('getCreatedAt')->willReturn(new \DateTime('2023-01-01'));
        $order->method('getItems')->willReturn([$item]);

        $this->orderRepo->method('findByUserId')
            ->with($userId)
            ->willReturn([$order]);

        // 3. Setup Product Lookup
        $product = $this->createMock(Product::class);
        $product->method('getTitle')->willReturn('Buku Keren');
        
        $this->productRepo->method('findById')
            ->with('prod-A')
            ->willReturn($product);

        // Execute
        $result = $this->useCase->execute($userId);

        // Assertions
        $this->assertCount(1, $result);
        $this->assertEquals('ord-1', $result[0]['id']);
        $this->assertEquals(10.0, $result[0]['total_usd']);
        
        // Cek apakah nama produk berhasil di-lookup
        $this->assertEquals('Buku Keren', $result[0]['items'][0]['product_name']);
    }

    public function test_returns_empty_array_if_no_orders()
    {
        $this->orderRepo->method('findByUserId')->willReturn([]);
        
        $result = $this->useCase->execute('user-empty');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
