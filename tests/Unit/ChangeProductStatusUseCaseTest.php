<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\ChangeProductStatusUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductContentRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class ChangeProductStatusUseCaseTest extends TestCase
{
    private $productRepo;
    private $contentRepo;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->contentRepo = $this->createMock(ProductContentRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new ChangeProductStatusUseCase(
            $this->productRepo,
            $this->contentRepo,
            $this->logger
        );
    }

    private function createMockProduct($sourcePath = null): Product
    {
        return new Product(
            'p1', 'c1', 'en', 'ebook', 'Title', 'Desc', 'a1', null, null, null,
            $sourcePath, // Source Path
            new Money(10, 'USD')
        );
    }

    public function test_publish_fails_if_no_content_and_no_master_file()
    {
        $product = $this->createMockProduct(null); // No master file
        
        $this->productRepo->method('findById')->willReturn($product);
        $this->contentRepo->method('countByProductId')->willReturn(0); // No chapters

        $this->expectException(StoreManagementException::class);
        $this->expectExceptionMessage('Cannot publish');

        $this->useCase->execute('p1', 'published');
    }

    public function test_publish_success_if_has_master_file()
    {
        $product = $this->createMockProduct('books/file.epub'); // Has master file
        $this->productRepo->method('findById')->willReturn($product);
        
        $this->productRepo->expects($this->once())->method('save');
        
        $this->useCase->execute('p1', 'published');
    }

    public function test_publish_success_if_has_chapters()
    {
        $product = $this->createMockProduct(null); // No master file
        $this->productRepo->method('findById')->willReturn($product);
        $this->contentRepo->method('countByProductId')->willReturn(5); // Has chapters
        
        $this->productRepo->expects($this->once())->method('save');
        
        $this->useCase->execute('p1', 'published');
    }
}
