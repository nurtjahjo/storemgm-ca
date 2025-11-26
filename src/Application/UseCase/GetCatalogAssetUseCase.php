<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use RuntimeException;

class GetCatalogAssetUseCase
{
    public function __construct(
        private StorageServiceInterface $storageService,
        private LoggerInterface $logger
    ) {}

    /**
     * Mengambil stream aset katalog (Cover/Audio Profile).
     * 
     * @param string $type 'cover' atau 'profile'
     * @param string $filename Nama file (misal: 'uuid.jpg' atau 'uuid_large.webp')
     */
    public function execute(string $type, string $filename)
    {
        // Sanitasi nama file (dasar) untuk mencegah path traversal di level aplikasi
        $filename = basename($filename);

        // Tentukan folder berdasarkan tipe
        $folder = match ($type) {
            'cover' => 'covers/',
            'profile' => 'profiles/', 
            default => throw new RuntimeException("Invalid asset type.")
        };

        $relativePath = $folder . $filename;

        try {
            // StorageService akan menangani pembacaan file fisik
            return $this->storageService->getCatalogAsset($relativePath);
        } catch (\Exception $e) {
            $this->logger->log("Asset not found: $relativePath", 'WARNING');
            throw new RuntimeException("Asset not found.");
        }
    }
}
