<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class UploadProductMasterFileUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private StorageServiceInterface $storageService,
        private LoggerInterface $logger
    ) {}

    /**
     * @param string $fileContent Isi file binary
     * @param string $originalName Nama file asli (untuk ekstensi)
     */
    public function execute(string $productId, string $fileContent, string $originalName): void
    {
        $product = $this->productRepo->findById($productId);
        if (!$product) throw new ProductNotFoundException("Product $productId not found.");

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['epub', 'zip', 'pdf'])) {
            throw new StoreManagementException("Invalid file type. Allowed: epub, zip, pdf.");
        }

        // Simpan di folder private: books/{uuid}.{ext}
        $relativePath = 'books/' . $productId . '.' . $ext;
        
        // Simpan file (Overwrite jika ada)
        $this->storageService->put($relativePath, $fileContent, true); // true = private

        // Update DB
        $product->setSourceFilePath($relativePath);
        $this->productRepo->save($product);

        $this->logger->log("Uploaded master file for product $productId");
    }
}
