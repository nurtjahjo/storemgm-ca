<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\ListProductsUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Application\DTO\PaginatedResult;

class ListProductsUseCaseTest extends TestCase
{
    private $productRepository;
    private $logger;
    private $useCase;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new ListProductsUseCase(
            $this->productRepository,
            $this->logger
        );
    }

    public function test_it_fetches_products_with_valid_parameters()
    {
        // Setup Mock Result
        $mockResult = new PaginatedResult([], 0, 1, 20);

        // Expectation: Repository dipanggil dengan parameter yang diteruskan
        $this->productRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(
                'id', // Language
                1,    // Page
                20,   // PerPage
                'created_at', // Sort
                'desc', // Dir
                null, // Category
                null  // Search
            )
            ->willReturn($mockResult);

        $result = $this->useCase->execute('id', 1, 20, 'created_at', 'desc');
        
        $this->assertInstanceOf(PaginatedResult::class, $result);
    }

    public function test_it_sanitizes_pagination_input()
    {
        $mockResult = new PaginatedResult([], 0, 1, 100);

        // Skenario: User minta page -5 dan perPage 500
        // Expectation: UseCase mengubahnya menjadi page 1 dan perPage 100 (max limit)
        $this->productRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(
                'en',
                1,    // -5 dikoreksi jadi 1
                100,  // 500 dikoreksi jadi 100
                'created_at',
                'desc',
                null,
                null
            )
            ->willReturn($mockResult);

        $this->useCase->execute('en', -5, 500, 'created_at', 'desc');
    }

    public function test_it_enforces_sorting_whitelist()
    {
        $mockResult = new PaginatedResult([], 0, 1, 20);

        // Skenario: User minta sort by 'password' (kolom berbahaya/tidak valid)
        // Expectation: UseCase mengubahnya menjadi default 'created_at'
        $this->productRepository->expects($this->once())
            ->method('findWithPagination')
            ->with(
                'id',
                1,
                20,
                'created_at', // 'password' dikoreksi jadi default
                'asc',
                null,
                null
            )
            ->willReturn($mockResult);

        $this->useCase->execute('id', 1, 20, 'password', 'asc');
    }
}
