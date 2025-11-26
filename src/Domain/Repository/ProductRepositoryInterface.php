<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Application\DTO\PaginatedResult;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    
    public function findById(string $id): ?Product;
    
    /**
     * Mengambil daftar produk dengan paginasi, filter bahasa, dan sorting.
     * Wajib menyertakan bahasa.
     */
    public function findWithPagination(
        string $language,
        int $page,
        int $perPage,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $categoryId = null,
        ?string $searchQuery = null
    ): PaginatedResult;

    // Metode tambahan untuk keperluan internal/migrasi
    public function findByOriginalId(int $originalId): ?Product;
}
