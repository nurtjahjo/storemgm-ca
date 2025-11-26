<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\WishlistRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class RemoveFromWishlistUseCase
{
    public function __construct(
        private WishlistRepositoryInterface $wishlistRepo,
        private LoggerInterface $logger
    ) {}

    public function execute(string $userId, string $productId): void
    {
        $this->wishlistRepo->remove($userId, $productId);
        $this->logger->log("User $userId removed product $productId from wishlist.");
    }
}
