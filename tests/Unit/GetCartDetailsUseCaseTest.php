<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetCartDetailsUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class GetCartDetailsUseCaseTest extends TestCase
{
    private $cartRepo;
    private $productRepo;
    private $useCase;

    protected function setUp(): void
    {
        $this->cartRepo = $this->createMock(CartRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->useCase = new GetCartDetailsUseCase($this->cartRepo, $this->productRepo);
    }

    public function test_returns_details_with_correct_totals()
    {
        // 1. Setup Cart
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-1');
        $item1 = new CartItem('i1', 'cart-1', 'p1', 2, 'buy');
        $item2 = new CartItem('i2', 'cart-1', 'p2', 1, 'rent');
        $cart->method('getItems')->willReturn([$item1, $item2]);
        $this->cartRepo->method('findByUserId')->willReturn($cart);

        // 2. Setup Products
        $prod1 = $this->createMockProduct('p1', 10, false); // Buy $10
        $prod2 = $this->createMockProduct('p2', 20, true, 5); // Rent $5 (Normal $20)

        $this->productRepo->method('findById')->willReturnMap([
            ['p1', $prod1],
            ['p2', $prod2],
        ]);

        // 3. Execute
        $result = $this->useCase->execute('u1', null);

        // 4. Assert
        $this->assertNotNull($result);
        // Total = (2 * 10) + (1 * 5) = 25
        $this->assertEquals(25.0, $result->grandTotal);
        $this->assertCount(2, $result->items);
        $this->assertEquals(5.0, $result->items[1]->pricePerUnit); // Item 2 harusnya harga sewa
    }

    private function createMockProduct($id, $price, $canRent = false, $rentPrice = null)
    {
        $p = $this->createMock(Product::class);
        $p->method('getId')->willReturn($id);
        $p->method('getTitle')->willReturn("Title $id");
        $p->method('getCoverImagePath')->willReturn("img.jpg");
        $p->method('getType')->willReturn("ebook");
        $p->method('getPriceUsd')->willReturn(new Money($price, 'USD'));
        $p->method('canRent')->willReturn($canRent);
        if ($rentPrice) {
            $p->method('getRentalPriceUsd')->willReturn(new Money($rentPrice, 'USD'));
        }
        return $p;
    }
}
