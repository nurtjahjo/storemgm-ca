<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductContentRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class ChangeProductStatusUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private ProductContentRepositoryInterface $contentRepo,
        private LoggerInterface $logger
    ) {}

    public function execute(string $productId, string $newStatus): void
    {
        $product = $this->productRepo->findById($productId);
        if (!$product) throw new ProductNotFoundException("Product not found.");

        // VALIDASI JIKA MAU PUBLISH
        if ($newStatus === 'published') {
            // 1. Cek Harga (Boleh 0, tapi tidak boleh null/unset di domain - di sini priceUsd selalu ada objectnya)
            // (Validasi sudah dijamin oleh Value Object Money yang tidak boleh negatif)
            
            // 2. Cek Ketersediaan Konten
            $hasMasterFile = !empty($product->getSourceFilePath());
            $hasChapters = $this->contentRepo->countByProductId($productId) > 0;

            if (!$hasMasterFile && !$hasChapters) {
                throw new StoreManagementException("Cannot publish: Product has no master file AND no content chapters.");
            }
        }

        $product->setStatus($newStatus);
        $this->productRepo->save($product);
        
        $this->logger->log("Product $productId status changed to $newStatus");
    }
}
