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
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new AddToCartUseCase(
            $this->cartRepository,
            $this->productRepository,
            $this->logger
        );
    }

    private function createMockProduct(string $id): Product
    {
        // Menggunakan Named Arguments agar aman dari perubahan urutan
        return new Product(
            id: $id,
            categoryId: 'cat-1',
            language: 'en',
            type: 'ebook',
            title: 'Title',
            synopsis: 'Synopsis',
            authorId: 'auth-1',
            narratorId: null,
            coverImagePath: null,
            profileAudioPath: null,
            sourceFilePath: null, // Kolom Baru
            priceUsd: new Money(10, 'USD'),
            canRent: false, // Kolom Baru
            rentalPriceUsd: null, // Kolom Baru
            rentalDurationDays: null, // Kolom Baru
            tags: null,
            status: 'published'
        );
    }

    public function test_throws_exception_if_product_not_found()
    {
        $this->expectException(ProductNotFoundException::class);
        $this->productRepository->method('findById')->willReturn(null);
        $this->useCase->execute('user-123', null, 'invalid-product-id');
    }

    public function test_creates_new_cart_if_user_has_none()
    {
        $product = $this->createMockProduct('prod-1');
        $this->productRepository->method('findById')->willReturn($product);
        $this->cartRepository->method('findByUserId')->willReturn(null);

        $this->cartRepository->expects($this->once())->method('save');
        $this->cartRepository->expects($this->once())->method('addItem');

        $this->useCase->execute('user-123', null, 'prod-1');
    }

    public function test_throws_exception_if_product_already_in_cart()
    {
        $this->expectException(ProductAlreadyInCartException::class);

        $product = $this->createMockProduct('prod-1');
        $this->productRepository->method('findById')->willReturn($product);

        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cart->method('hasProduct')->with('prod-1')->willReturn(true);

        $this->cartRepository->method('findByUserId')->willReturn($cart);

        $this->useCase->execute('user-123', null, 'prod-1');
    }
    
    public function test_adds_item_to_existing_cart()
    {
        $product = $this->createMockProduct('prod-1');
        $this->productRepository->method('findById')->willReturn($product);

        $cart = $this->createMock(Cart::class);
        $cart->method('getId')->willReturn('cart-abc');
        $cart->method('hasProduct')->with('prod-1')->willReturn(false);

        $this->cartRepository->method('findByUserId')->willReturn($cart);

        $this->cartRepository->expects($this->never())->method('save');
        $this->cartRepository->expects($this->once())->method('addItem');

        $this->useCase->execute('user-123', null, 'prod-1');
    }
}
