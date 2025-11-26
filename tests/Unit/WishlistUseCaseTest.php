<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\AddToWishlistUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserWishlistUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\WishlistRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Entity\Wishlist;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class WishlistUseCaseTest extends TestCase
{
    private $wishlistRepo;
    private $productRepo;
    private $logger;

    protected function setUp(): void
    {
        $this->wishlistRepo = $this->createMock(WishlistRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function test_add_to_wishlist_success()
    {
        $useCase = new AddToWishlistUseCase($this->wishlistRepo, $this->productRepo, $this->logger);
        $product = $this->createMock(Product::class);
        
        $this->productRepo->method('findById')->with('p1')->willReturn($product);
        $this->wishlistRepo->method('exists')->willReturn(false);
        
        $this->wishlistRepo->expects($this->once())->method('save');
        
        $useCase->execute('u1', 'p1');
    }

    public function test_add_to_wishlist_throws_if_product_missing()
    {
        $useCase = new AddToWishlistUseCase($this->wishlistRepo, $this->productRepo, $this->logger);
        $this->productRepo->method('findById')->willReturn(null);
        
        $this->expectException(ProductNotFoundException::class);
        $useCase->execute('u1', 'missing');
    }

    public function test_get_user_wishlist_hydrates_products()
    {
        $useCase = new GetUserWishlistUseCase($this->wishlistRepo, $this->productRepo);
        
        $wishlistItems = [
            new Wishlist('w1', 'u1', 'p1')
        ];
        
        $product = new Product('p1', 'c1', 'en', 'ebook', 'Book 1', '', 'a1', null, null, null, null, new Money(10,'USD'));

        $this->wishlistRepo->method('findByUserId')->willReturn($wishlistItems);
        $this->productRepo->method('findById')->with('p1')->willReturn($product);
        
        $result = $useCase->execute('u1');
        
        $this->assertCount(1, $result);
        $this->assertEquals('Book 1', $result[0]['title']);
    }
}
