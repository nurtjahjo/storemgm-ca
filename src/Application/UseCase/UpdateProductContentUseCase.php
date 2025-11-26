<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductContentRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\ProductContent;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Ramsey\Uuid\Uuid;

class UpdateProductContentUseCase
{
    public function __construct(
        private ProductContentRepositoryInterface $contentRepo,
        private StorageServiceInterface $storageService,
        private LoggerInterface $logger
    ) {}

    public function execute(
        string $productId, 
        ?string $contentId, 
        string $title, 
        int $order, 
        ?string $textContent, 
        ?string $audioContent
    ): void {
        $id = $contentId ?? Uuid::uuid4()->toString();
        
        // Handle Files
        $textPath = null;
        if ($textContent) {
            $textPath = 'narrations/' . $id . '.html';
            $this->storageService->put($textPath, $textContent, true);
        }

        $audioPath = null;
        if ($audioContent) {
            $audioPath = 'audios/' . $id . '.mp3';
            $this->storageService->put($audioPath, $audioContent, true);
        }

        // Jika update, kita perlu path lama jika tidak diupload baru
        if ($contentId && (!$textPath || !$audioPath)) {
            $existing = $this->contentRepo->findById($contentId);
            if ($existing) {
                $textPath = $textPath ?? $existing->getContentTextPath();
                $audioPath = $audioPath ?? $existing->getContentAudioPath();
            }
        }

        $content = new ProductContent(
            $id, $productId, $title, $order, $textPath, $audioPath,
            0, 0, 'approved' // Default approved for admin update
        );

        $this->contentRepo->save($content);
        $this->logger->log("Updated content chapter for product $productId");
    }
}
