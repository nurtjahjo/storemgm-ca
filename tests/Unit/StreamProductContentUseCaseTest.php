<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\StreamProductContentUseCase;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;
use PDO;
use PDOStatement;

class StreamProductContentUseCaseTest extends TestCase
{
    private $libraryRepo;
    private $productRepo;
    private $storageService;
    private $logger;
    private $pdo;
    private $useCase;

    protected function setUp(): void
    {
        // Mock semua dependensi
        $this->libraryRepo = $this->createMock(UserLibraryRepositoryInterface::class);
        $this->productRepo = $this->createMock(ProductRepositoryInterface::class);
        $this->storageService = $this->createMock(StorageServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pdo = $this->createMock(PDO::class);

        $this->useCase = new StreamProductContentUseCase(
            $this->libraryRepo,
            $this->productRepo,
            $this->storageService,
            $this->logger,
            $this->pdo
        );
    }

    public function test_it_denies_access_if_no_valid_license_found()
    {
        // Skenario: User belum beli atau masa sewa habis
        // Repository mengembalikan NULL
        $this->libraryRepo->method('findValidAccess')->willReturn(null);

        // Assert: Harap melempar exception 403
        $this->expectException(StoreManagementException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('You do not have an active license');

        $this->useCase->execute('user-1', 'prod-1', 'content-1', 'text');
    }

    public function test_it_streams_chapter_content_if_access_is_valid()
    {
        // 1. Setup: Valid License (Mock UserLibrary)
        $validLicense = $this->createMock(UserLibrary::class);
        $this->libraryRepo->method('findValidAccess')->willReturn($validLicense);

        // 2. Setup: Mock PDO untuk ambil path chapter dari database
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        // Simulasi hasil query DB
        $stmt->method('fetch')->willReturn([
            'content_text_path' => 'narrations/uuid-123.html',
            'content_audio_path' => 'audios/uuid-123.mp3'
        ]);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT content_text_path'))
            ->willReturn($stmt);

        // 3. Expectation: StorageService dipanggil dengan path yang benar
        $this->storageService->expects($this->once())
            ->method('getPrivateContent')
            ->with('narrations/uuid-123.html')
            ->willReturn('fake-stream-resource');

        // Execute
        $result = $this->useCase->execute('user-1', 'prod-1', 'content-1', 'text');
        
        $this->assertEquals('fake-stream-resource', $result);
    }

    public function test_it_streams_source_file_epub()
    {
        // 1. Setup: Valid License
        $validLicense = $this->createMock(UserLibrary::class);
        $this->libraryRepo->method('findValidAccess')->willReturn($validLicense);

        // 2. Setup: Product dengan source file path
        $product = $this->createMock(Product::class);
        $product->method('getSourceFilePath')->willReturn('books/full_book.epub');
        
        $this->productRepo->method('findById')->willReturn($product);

        // 3. Expectation: StorageService dipanggil
        $this->storageService->expects($this->once())
            ->method('getPrivateContent')
            ->with('books/full_book.epub')
            ->willReturn('fake-epub-stream');

        // Execute dengan type 'source'
        $result = $this->useCase->execute('user-1', 'prod-1', 'source', 'source');
        
        $this->assertEquals('fake-epub-stream', $result);
    }
}
