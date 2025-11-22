<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class GetProductDetailsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(string $id): Product
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            $this->logger->log("Product not found: {$id}", 'WARNING');
            throw new ProductNotFoundException("Product with ID {$id} not found.");
        }

        // Di sini kita bisa menambahkan logika bisnis tambahan jika perlu,
        // misalnya: mengecek apakah produk ini 'active'/'published'.
        // Namun untuk admin atau pemilik, mungkin perlu melihat draft.
        // Untuk katalog publik, Adapter sebaiknya memfilter status.
        
        if ($product->getStatus() !== 'published') {
             // Opsional: Jika ingin strict di level Core bahwa user biasa tidak boleh lihat draft
             // throw new ProductNotFoundException("Product not available.");
        }

        return $product;
    }
}
