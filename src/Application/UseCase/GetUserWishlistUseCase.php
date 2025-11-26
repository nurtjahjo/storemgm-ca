<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\WishlistRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;

class GetUserWishlistUseCase
{
    public function __construct(
        private WishlistRepositoryInterface $wishlistRepo,
        private ProductRepositoryInterface $productRepo
    ) {}

    public function execute(string $userId): array
    {
        $items = $this->wishlistRepo->findByUserId($userId);
        $result = [];

        foreach ($items as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            
            // Jika produk sudah dihapus/tidak ada, skip dari list wishlist
            if (!$product) continue;

            // Kita kembalikan data produk yang di-hydrate agar frontend bisa menampilkan card
            $result[] = $product->toArray(); 
        }

        return $result;
    }
}
