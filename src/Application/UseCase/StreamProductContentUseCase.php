<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use RuntimeException;
use PDO;

class StreamProductContentUseCase
{
    public function __construct(
        private UserLibraryRepositoryInterface $libraryRepo, // <-- Pake ini sekarang
        private ProductRepositoryInterface $productRepo,
        private StorageServiceInterface $storageService,
        private LoggerInterface $logger,
        private PDO $pdo
    ) {}

    public function execute(string $userId, string $productId, ?string $contentId, string $type)
    {
        // 1. Validasi Akses (Cek Tabel User Library, bukan Order)
        $access = $this->libraryRepo->findValidAccess($userId, $productId);

        if (!$access) {
            $this->logger->log("Access Denied (No active license): User $userId -> Product $productId", 'WARNING');
            throw new StoreManagementException("You do not have an active license (buy/rent) for this product.", 403);
        }

        $relativePath = null;

        // 2. Tentukan Path File
        if ($type === 'source') {
            // Ambil file master (EPUB/ZIP)
            $product = $this->productRepo->findById($productId);
            if (!$product) throw new RuntimeException("Product not found.");
            $relativePath = $product->getSourceFilePath();
        } else {
            // Stream Bab (Text/Audio)
            if (!$contentId) throw new RuntimeException("Content ID required.");
            
            $stmt = $this->pdo->prepare("SELECT content_text_path, content_audio_path FROM storemgm_product_contents WHERE id = ? AND product_id = ?");
            $stmt->execute([$contentId, $productId]);
            $content = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$content) throw new RuntimeException("Chapter not found.");

            $relativePath = ($type === 'text') ? $content['content_text_path'] : $content['content_audio_path'];
        }

        if (empty($relativePath)) throw new RuntimeException("File path unavailable.");

        // 3. Stream dari Private Storage
        return $this->storageService->getPrivateContent($relativePath);
    }
}
