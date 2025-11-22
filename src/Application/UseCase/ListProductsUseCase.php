<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Application\DTO\PaginatedResult;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class ListProductsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Mengambil daftar produk untuk katalog.
     * Logika sorting dan filtering terjadi di sini (Server Authority),
     * sehingga Frontend menerima data yang sudah terurut dan valid.
     */
    public function execute(
        string $language,
        int $page = 1,
        int $perPage = 20,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $categoryId = null,
        ?string $searchQuery = null
    ): PaginatedResult {
        
        // 1. Sanitasi Input Dasar
        $page = max(1, $page);
        
        // Batasi perPage agar klien tidak meminta terlalu banyak data sekaligus (Security)
        // Frontend boleh meminta data, tapi Server yang menentukan batasnya.
        $perPage = max(1, min($perPage, 100)); 
        
        // 2. Whitelist Sorting (Security)
        // Mencegah SQL Injection via parameter sort
        $allowedSorts = ['price_usd', 'created_at', 'title', 'published_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Logging untuk debugging performa katalog
        $this->logger->log("Catalog Query: Lang={$language}, Page={$page}, Cat=" . ($categoryId ?? 'All'));

        // 3. Panggil Repository
        // Repository akan mengembalikan PaginatedResult berisi array Entity Product
        $result = $this->productRepository->findWithPagination(
            $language,
            $page,
            $perPage,
            $sortBy,
            $sortDir,
            $categoryId,
            $searchQuery
        );

        // Catatan: Controller/Adapter nanti akan mengubah Entity Product menjadi Array/JSON.
        // Pastikan Entity Product::toArray() menyertakan 'updated_at' agar 
        // Frontend bisa melakukan sinkronisasi cerdas dengan IndexedDB.
        
        return $result;
    }
}
