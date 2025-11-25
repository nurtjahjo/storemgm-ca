<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Application\DTO\CartDetails;
use Nurtjahjo\StoremgmCA\Application\DTO\CartItemDetail;

class GetCartDetailsUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepo,
        private ProductRepositoryInterface $productRepo
    ) {}

    public function execute(?string $userId, ?string $guestId): ?CartDetails
    {
        $cart = null;
        if ($userId) {
            $cart = $this->cartRepo->findByUserId($userId);
        } elseif ($guestId) {
            $cart = $this->cartRepo->findByGuestId($guestId);
        }

        if (!$cart) {
            return null;
        }

        $itemDetails = [];
        $grandTotal = 0.0;

        foreach ($cart->getItems() as $item) {
            $product = $this->productRepo->findById($item->getProductId());
            
            // Jika produk dihapus/tidak aktif, kita skip dari display (atau beri tanda)
            if (!$product) continue; 

            // Tentukan harga (Beli vs Sewa)
            $priceObj = $product->getPriceUsd();
            if ($item->getPurchaseType() === 'rent' && $product->canRent()) {
                $priceObj = $product->getRentalPriceUsd() ?? $priceObj;
            }
            
            $price = $priceObj->getAmount();
            $total = $price * $item->getQuantity();
            $grandTotal += $total;

            $itemDetails[] = new CartItemDetail(
                $product->getId(),
                $product->getTitle(),
                $product->getCoverImagePath() ?? '',
                $item->getQuantity(),
                $price,
                $total,
                $item->getPurchaseType(),
                $product->getType()
            );
        }

        return new CartDetails(
            $cart->getId(),
            $itemDetails,
            $grandTotal,
            'USD'
        );
    }
}
