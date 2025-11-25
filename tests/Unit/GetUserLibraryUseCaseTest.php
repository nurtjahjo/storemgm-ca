<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserLibraryUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use DateTime;

class GetUserLibraryUseCaseTest extends TestCase
{
    private $libRepo;
    private $prodRepo;
    private $useCase;

    protected function setUp(): void
    {
        $this->libRepo = $this->createMock(UserLibraryRepositoryInterface::class);
        $this->prodRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->useCase = new GetUserLibraryUseCase($this->libRepo, $this->prodRepo);
    }

    public function test_returns_active_and_expired_items_correctly()
    {
        // 1. Setup Library Items
        $ownedItem = $this->createMock(UserLibrary::class);
        $ownedItem->method('getProductId')->willReturn('p1');
        $ownedItem->method('getAccessType')->willReturn('owned');
        $ownedItem->method('getExpiresAt')->willReturn(null);

        $rentedActive = $this->createMock(UserLibrary::class);
        $rentedActive->method('getProductId')->willReturn('p2');
        $rentedActive->method('getAccessType')->willReturn('rented');
        
        // PERBAIKAN: Tambah +1 hour buffer untuk kompensasi waktu eksekusi
        $rentedActive->method('getExpiresAt')->willReturn((new DateTime())->modify('+5 days +1 hour'));

        $rentedExpired = $this->createMock(UserLibrary::class);
        $rentedExpired->method('getProductId')->willReturn('p3');
        $rentedExpired->method('getAccessType')->willReturn('rented');
        $rentedExpired->method('getExpiresAt')->willReturn((new DateTime())->modify('-1 day'));

        $this->libRepo->method('findByUserId')->willReturn([$ownedItem, $rentedActive, $rentedExpired]);

        // 2. Setup Product Mock
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn('px');
        // Mock method lain yang mungkin dipanggil oleh DTO
        $product->method('getTitle')->willReturn('Test Title');
        $product->method('getCoverImagePath')->willReturn('cover.jpg');
        $product->method('getType')->willReturn('ebook');
        
        $this->prodRepo->method('findById')->willReturn($product);

        // 3. Execute
        $results = $this->useCase->execute('u1');

        // 4. Assert
        $this->assertCount(3, $results);
        
        // Item 1: Owned
        $this->assertFalse($results[0]->isExpired);
        $this->assertEquals('owned', $results[0]->accessType);
        
        // Item 2: Rented Active
        $this->assertFalse($results[1]->isExpired);
        // Sekarang aman untuk assert 5 karena kita sudah tambah buffer
        $this->assertEquals(5, $results[1]->daysRemaining);

        // Item 3: Rented Expired
        $this->assertTrue($results[2]->isExpired);
    }
}
