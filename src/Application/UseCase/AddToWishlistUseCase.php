<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\WishlistRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Wishlist;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Ramsey\Uuid\Uuid;

class AddToWishlistUseCase
{
    public function __construct(
        private WishlistRepositoryInterface $wishlistRepo,
        private ProductRepositoryInterface $productRepo,
        private LoggerInterface $logger
    ) {}

    public function execute(string $userId, string $productId): void
    {
        // 1. Validasi Produk Ada
        $product = $this->productRepo->findById($productId);
        if (!$product) {
            throw new ProductNotFoundException("Product ID $productId not found.");
        }

        // 2. Cek Duplikasi (Idempotency)
        if ($this->wishlistRepo->exists($userId, $productId)) {
            return; // Sudah ada, anggap sukses
        }

        // 3. Simpan
        $wishlist = new Wishlist(
            Uuid::uuid4()->toString(),
            $userId,
            $productId
        );
        $this->wishlistRepo->save($wishlist);
        
        $this->logger->log("User $userId added product $productId to wishlist.");
    }
}
