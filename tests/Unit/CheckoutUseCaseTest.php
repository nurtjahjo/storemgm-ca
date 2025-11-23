<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\CheckoutUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\CurrencyConverterInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;
use Nurtjahjo\StoremgmCA\Domain\Exception\CartIsEmptyException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;

class CheckoutUseCaseTest extends TestCase
{
    private $cartRepo;
    private $orderRepo;
    private $productRepo;
    private $paymentGateway;
    private $currencyConverter;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        // Mock semua dependensi
        $this->cartRepo = $this->createMock(CartRepositoryInterface::class);
        $this->orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->currencyConverter = $this->createMock(CurrencyConverterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new CheckoutUseCase(
            $this->cartRepo,
            $this->orderRepo,
            $this->productRepo,
            $this->paymentGateway,
            $this->currencyConverter,
            $this->logger
        );
    }

    public function test_throws_exception_if_cart_is_empty()
    {
        $this->expectException(CartIsEmptyException::class);

        // Skenario: Cart tidak ditemukan atau item kosong
        $this->cartRepo->method('findByUserId')->willReturn(new Cart('cart-1', 'user-1', null)); 

        $this->useCase->execute('user-1', 'en', []);
    }

    public function test_successful_checkout_flow_usd()
    {
        $userId = 'user-123';
        
        // 1. Setup Cart dengan 1 Item
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cartItem = new CartItem('item-1', 'cart-abc', 'prod-1', 2); // Beli 2 pcs
        $cart->method('getItems')->willReturn([$cartItem]);
        
        $this->cartRepo->method('findByUserId')->with($userId)->willReturn($cart);

        // 2. Setup Product ($10)
        $product = new Product('prod-1', 'cat-1', 'en', 'ebook', 'Book Title', 'Desc', 'auth-1', null, null, null, new Money(10.00, 'USD'), null, 'published');
        $this->productRepo->method('findById')->with('prod-1')->willReturn($product);

        // 3. Expectation: Order Repository Save dipanggil
        // Total harusnya 2 * 10 = $20
        $this->orderRepo->expects($this->atLeastOnce())
            ->method('save')
            ->with($this->callback(function (Order $order) use ($userId) {
                return $order->getUserId() === $userId &&
                       $order->getTotalPriceUsd()->getAmount() === 20.00 &&
                       $order->getStatus() === 'pending';
            }));

        // 4. Expectation: Payment Gateway Create Transaction dipanggil
        $mockPaymentResponse = ['redirect_url' => 'https://payment.url'];
        $this->paymentGateway->expects($this->once())
            ->method('createTransaction')
            ->willReturn($mockPaymentResponse);

        // 5. Expectation: Cart Dihapus
        $this->cartRepo->expects($this->once())->method('delete')->with('cart-abc');

        // Execute
        $result = $this->useCase->execute($userId, 'en', []);

        $this->assertArrayHasKey('order_id', $result);
        $this->assertEquals($mockPaymentResponse, $result['payment']);
    }

    public function test_checkout_flow_with_idr_conversion()
    {
        $userId = 'user-idr';
        
        // 1. Cart Item
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cartItem = new CartItem('item-1', 'cart-abc', 'prod-1', 1);
        $cart->method('getItems')->willReturn([$cartItem]);
        $this->cartRepo->method('findByUserId')->willReturn($cart);

        // 2. Product ($10)
        $product = new Product('prod-1', 'cat-1', 'en', 'ebook', 'Book Title', 'Desc', 'auth-1', null, null, null, new Money(10.00, 'USD'), null, 'published');
        $this->productRepo->method('findById')->willReturn($product);

        // 3. Setup Currency Converter
        // Rate 1 USD = 15000 IDR
        $this->currencyConverter->expects($this->once())
            ->method('getExchangeRate')
            ->with('USD', 'IDR')
            ->willReturn(15000.0);

        // 4. Expectation: Order disimpan dengan nilai IDR yang benar
        $this->orderRepo->expects($this->atLeastOnce())
            ->method('save')
            ->with($this->callback(function (Order $order) {
                // Total USD $10, Rate 15000 -> Total IDR 150,000
                return $order->getTotalPriceIdr() === 150000.0;
            }));

        $this->paymentGateway->method('createTransaction')->willReturn([]);

        // Execute dengan locale 'id'
        $this->useCase->execute($userId, 'id', []);
    }
}
