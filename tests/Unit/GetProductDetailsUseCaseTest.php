<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetProductDetailsUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class GetProductDetailsUseCaseTest extends TestCase
{
    private $productRepository;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new GetProductDetailsUseCase(
            $this->productRepository,
            $this->logger
        );
    }

    public function test_it_returns_product_when_found()
    {
        $productId = 'prod-123';
        $mockProduct = new Product(
            id: $productId,
            categoryId: 'cat-1',
            language: 'en',
            type: 'ebook',
            title: 'Test Book',
            synopsis: 'Desc',
            authorId: 'auth-1',
            narratorId: null,
            coverImagePath: null,
            profileAudioPath: null,
            sourceFilePath: null,
            priceUsd: new Money(10, 'USD'),
            canRent: false,
            rentalPriceUsd: null,
            rentalDurationDays: null,
            tags: null,
            status: 'published'
        );

        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn($mockProduct);

        $result = $this->useCase->execute($productId);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($productId, $result->getId());
    }

    public function test_it_throws_exception_when_product_not_found()
    {
        $productId = 'missing-id';
        $this->productRepository->expects($this->once())
            ->method('findById')
            ->with($productId)
            ->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        $this->useCase->execute($productId);
    }
}
