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

    private function createMockProduct(string $id, float $price): Product
    {
        return new Product(
            id: $id,
            categoryId: 'cat-1',
            language: 'en',
            type: 'ebook',
            title: 'Book Title',
            synopsis: 'Desc',
            authorId: 'auth-1',
            narratorId: null,
            coverImagePath: null,
            profileAudioPath: null,
            sourceFilePath: null,
            priceUsd: new Money($price, 'USD'),
            canRent: false,
            rentalPriceUsd: null,
            rentalDurationDays: null,
            tags: null,
            status: 'published'
        );
    }

    public function test_throws_exception_if_cart_is_empty()
    {
        $this->expectException(CartIsEmptyException::class);
        $this->cartRepo->method('findByUserId')->willReturn(new Cart('cart-1', 'user-1', null)); 
        $this->useCase->execute('user-1', 'en');
    }

    public function test_successful_checkout_flow_usd()
    {
        $userId = 'user-123';
        
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cartItem = new CartItem('item-1', 'cart-abc', 'prod-1', 2); 
        $cart->method('getItems')->willReturn([$cartItem]);
        
        $this->cartRepo->method('findByUserId')->with($userId)->willReturn($cart);

        $product = $this->createMockProduct('prod-1', 10.00);
        $this->productRepo->method('findById')->with('prod-1')->willReturn($product);

        $this->orderRepo->expects($this->atLeastOnce())
            ->method('save')
            ->with($this->callback(function (Order $order) use ($userId) {
                return $order->getUserId() === $userId &&
                       $order->getTotalPriceUsd()->getAmount() === 20.00 &&
                       $order->getStatus() === 'pending';
            }));

        $mockPaymentResponse = ['redirect_url' => 'https://payment.url'];
        $this->paymentGateway->expects($this->once())
            ->method('createTransaction')
            ->willReturn($mockPaymentResponse);

        $this->cartRepo->expects($this->once())->method('delete')->with('cart-abc');

        $result = $this->useCase->execute($userId, 'en');

        $this->assertArrayHasKey('order_id', $result);
        $this->assertEquals($mockPaymentResponse, $result['payment']);
    }

    public function test_checkout_flow_with_idr_conversion()
    {
        $userId = 'user-idr';
        
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cartItem = new CartItem('item-1', 'cart-abc', 'prod-1', 1);
        $cart->method('getItems')->willReturn([$cartItem]);
        $this->cartRepo->method('findByUserId')->willReturn($cart);

        $product = $this->createMockProduct('prod-1', 10.00);
        $this->productRepo->method('findById')->willReturn($product);

        $this->currencyConverter->expects($this->once())
            ->method('getExchangeRate')
            ->with('USD', 'IDR')
            ->willReturn(15000.0);

        $this->orderRepo->expects($this->atLeastOnce())
            ->method('save')
            ->with($this->callback(function (Order $order) {
                return $order->getTotalPriceIdr() === 150000.0;
            }));

        $this->paymentGateway->method('createTransaction')->willReturn([]);

        $this->useCase->execute($userId, 'id');
    }
}
