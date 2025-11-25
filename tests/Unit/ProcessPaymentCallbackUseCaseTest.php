<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\ProcessPaymentCallbackUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface; // <-- Tambahan Baru
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Order;
use Nurtjahjo\StoremgmCA\Domain\Entity\OrderItem;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;
use RuntimeException;

class ProcessPaymentCallbackUseCaseTest extends TestCase
{
    private $orderRepo;
    private $libraryRepo;
    private $productRepo;
    private $paymentGateway; // <-- Tambahan Baru
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        $this->orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $this->libraryRepo = $this->createMock(UserLibraryRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class); // <-- Mock Baru
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new ProcessPaymentCallbackUseCase(
            $this->orderRepo,
            $this->libraryRepo,
            $this->productRepo,
            $this->paymentGateway, // <-- Argumen ke-4
            $this->logger,         // <-- Argumen ke-5
            'mock-server-key'      // <-- Argumen ke-6 (String Key)
        );
    }

    public function test_throws_exception_if_order_not_found()
    {
        // Bypass security check untuk test ini
        $this->paymentGateway->method('validateSignature')->willReturn(true);

        $this->orderRepo->method('findById')->willReturn(null);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order not found');

        $this->useCase->execute(['order_id' => 'missing', 'transaction_status' => 'settlement']);
    }

    public function test_marks_order_as_failed_on_failure_callback()
    {
        // Bypass security check
        $this->paymentGateway->method('validateSignature')->willReturn(true);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('ord-1');
        
        $this->orderRepo->method('findById')->willReturn($order);

        // Expectation: Set Status Failed & Save
        $order->expects($this->once())->method('setStatus')->with('failed');
        $this->orderRepo->expects($this->once())->method('save')->with($order);
        
        // Expectation: Library TIDAK boleh disentuh
        $this->libraryRepo->expects($this->never())->method('save');

        $this->useCase->execute(['order_id' => 'ord-1', 'transaction_status' => 'deny']);
    }

    public function test_grants_permanent_access_for_buy_purchase()
    {
        // Bypass security check
        $this->paymentGateway->method('validateSignature')->willReturn(true);

        // 1. Setup Order & Items
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('ord-1');
        $order->method('getUserId')->willReturn('user-1');
        $order->method('getStatus')->willReturn('pending');

        $item = new OrderItem('item-1', 'ord-1', 'prod-1', 1, new Money(10, 'USD'), 'buy'); // BUY
        $order->method('getItems')->willReturn([$item]);

        $this->orderRepo->method('findById')->willReturn($order);

        // 2. Setup Product
        $product = $this->createMock(Product::class);
        $this->productRepo->method('findById')->with('prod-1')->willReturn($product);

        // 3. Expectation: Library Save dipanggil dengan 'owned'
        $this->libraryRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserLibrary $lib) {
                return $lib->getUserId() === 'user-1' &&
                       $lib->getProductId() === 'prod-1' &&
                       $lib->getExpiresAt() === null; // Permanen = Null Expiry
            }));

        // 4. Expectation: Order completed
        $order->expects($this->once())->method('setStatus')->with('completed');

        $this->useCase->execute(['order_id' => 'ord-1', 'transaction_status' => 'settlement']);
    }

    public function test_grants_temporary_access_for_rent_purchase()
    {
        // Bypass security check
        $this->paymentGateway->method('validateSignature')->willReturn(true);

        // 1. Setup Order & Items
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn('ord-1');
        $order->method('getUserId')->willReturn('user-1');
        $order->method('getStatus')->willReturn('pending');

        $item = new OrderItem('item-1', 'ord-1', 'prod-1', 1, new Money(2, 'USD'), 'rent'); // RENT
        $order->method('getItems')->willReturn([$item]);

        $this->orderRepo->method('findById')->willReturn($order);

        // 2. Setup Product (Sewa 7 Hari)
        $product = $this->createMock(Product::class);
        $product->method('getRentalDurationDays')->willReturn(7);
        $this->productRepo->method('findById')->willReturn($product);

        // 3. Expectation: Library Save dipanggil dengan Tanggal Expired
        $this->libraryRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserLibrary $lib) {
                // Cek apakah expiresAt ada dan sekitar 7 hari lagi
                $now = new \DateTime();
                $diff = $lib->getExpiresAt()->diff($now)->days;
                return $diff >= 6 && $diff <= 7; // Toleransi waktu eksekusi
            }));

        $this->useCase->execute(['order_id' => 'ord-1', 'transaction_status' => 'capture']);
    }

    public function test_ignores_callback_if_order_already_completed()
    {
        // Bypass security check
        $this->paymentGateway->method('validateSignature')->willReturn(true);

        // Idempotency Check
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn('completed'); // Sudah selesai

        $this->orderRepo->method('findById')->willReturn($order);

        // Expectation: Tidak melakukan apa-apa lagi
        $this->libraryRepo->expects($this->never())->method('save');
        $this->orderRepo->expects($this->never())->method('save');

        $this->useCase->execute(['order_id' => 'ord-1', 'transaction_status' => 'settlement']);
    }
}
