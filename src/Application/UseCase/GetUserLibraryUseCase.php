<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Application\DTO\LibraryItemDetail;
use DateTime;

class GetUserLibraryUseCase
{
    public function __construct(
        private UserLibraryRepositoryInterface $libraryRepo,
        private ProductRepositoryInterface $productRepo
    ) {}

    /**
     * @return LibraryItemDetail[]
     */
    public function execute(string $userId): array
    {
        $libraryItems = $this->libraryRepo->findByUserId($userId);
        $result = [];
        $now = new DateTime();

        foreach ($libraryItems as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            if (!$product) continue;

            $isExpired = false;
            $daysRemaining = null;

            if ($item->getExpiresAt()) {
                $isExpired = $item->getExpiresAt() < $now;
                if (!$isExpired) {
                    $diff = $now->diff($item->getExpiresAt());
                    $daysRemaining = $diff->days;
                }
            }

            $result[] = new LibraryItemDetail(
                $product->getId(),
                $product->getTitle(),
                $product->getCoverImagePath() ?? '',
                $product->getType(),
                $item->getAccessType(),
                $item->getExpiresAt()?->format('c'), // ISO format
                $isExpired,
                $daysRemaining
            );
        }

        return $result;
    }
}
