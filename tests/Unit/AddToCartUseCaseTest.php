<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\AddToCartUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductAlreadyInCartException;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class AddToCartUseCaseTest extends TestCase
{
    private $cartRepository;
    private $productRepository;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        // Kita MOCK (tiru) semua dependensi eksternal
        // Ini membuktikan kita bisa test logic tanpa database asli
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new AddToCartUseCase(
            $this->cartRepository,
            $this->productRepository,
            $this->logger
        );
    }

    public function test_throws_exception_if_product_not_found()
    {
        $this->expectException(ProductNotFoundException::class);

        // Skenario: Repository produk mengembalikan null (tidak ketemu)
        $this->productRepository->method('findById')->willReturn(null);

        $this->useCase->execute('user-123', null, 'invalid-product-id');
    }

    public function test_creates_new_cart_if_user_has_none()
    {
        // 1. Setup Mock Product (Produk harus ada)
        $product = new Product(
            'prod-1', 'cat-1', 'en', 'ebook', 'Title', 'Synopsis', 'auth-1', null, null, null, 
            new Money(10, 'USD')
        );
        $this->productRepository->method('findById')->willReturn($product);

        // 2. Setup Mock Cart (User belum punya keranjang -> return null)
        $this->cartRepository->method('findByUserId')->willReturn(null);

        // 3. Expectation: Repository harus dipanggil method 'save' (buat cart baru)
        // dan method 'addItem' (tambah barang)
        $this->cartRepository->expects($this->once())->method('save');
        $this->cartRepository->expects($this->once())->method('addItem');

        // 4. Execute
        $this->useCase->execute('user-123', null, 'prod-1');
    }

    public function test_throws_exception_if_product_already_in_cart()
    {
        $this->expectException(ProductAlreadyInCartException::class);

        // 1. Setup Product
        $product = new Product(
            'prod-1', 'cat-1', 'en', 'ebook', 'Title', 'Synopsis', 'auth-1', null, null, null, 
            new Money(10, 'USD')
        );
        $this->productRepository->method('findById')->willReturn($product);

        // 2. Setup Existing Cart
        // Kita buat Cart palsu yang seolah-olah sudah berisi 'prod-1'
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        
        // KUNCI: Method hasProduct mengembalikan TRUE
        $cart->method('hasProduct')->with('prod-1')->willReturn(true);

        $this->cartRepository->method('findByUserId')->willReturn($cart);

        // 3. Execute (harus error)
        $this->useCase->execute('user-123', null, 'prod-1');
    }
    
    public function test_adds_item_to_existing_cart()
    {
        // 1. Setup Product
        $product = new Product(
            'prod-1', 'cat-1', 'en', 'ebook', 'Title', 'Synopsis', 'auth-1', null, null, null, 
            new Money(10, 'USD')
        );
        $this->productRepository->method('findById')->willReturn($product);

        // 2. Setup Existing Cart
        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        // KUNCI: Produk BELUM ada di keranjang
        $cart->method('hasProduct')->with('prod-1')->willReturn(false);

        $this->cartRepository->method('findByUserId')->willReturn($cart);

        // 3. Expectation: TIDAK buat cart baru (save), tapi LANGSUNG tambah item (addItem)
        $this->cartRepository->expects($this->never())->method('save'); // Jangan panggil save
        $this->cartRepository->expects($this->once())->method('addItem'); // Harus panggil addItem

        // 4. Execute
        $this->useCase->execute('user-123', null, 'prod-1');
    }
}
