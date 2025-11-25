<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Entity\CartItem;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductAlreadyInCartException;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Ramsey\Uuid\Uuid;

class AddToCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(?string $userId, ?string $guestCartId, string $productId, string $purchaseType = 'buy'): void
    {
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new ProductNotFoundException("Product ID {$productId} not found.");
        }

        // TODO: Bisa tambahkan validasi disini, misal jika purchaseType='rent' tapi product->canRent() false, throw error.

        $cart = null;
        if ($userId) {
            $cart = $this->cartRepository->findByUserId($userId);
        } elseif ($guestCartId) {
            $cart = $this->cartRepository->findByGuestId($guestCartId);
        } else {
            $guestCartId = Uuid::uuid4()->toString();
        }

        if (!$cart) {
            $cartId = Uuid::uuid4()->toString();
            $cart = new Cart($cartId, $userId, $guestCartId);
            $this->cartRepository->save($cart);
        }

        if ($cart->hasProduct($productId)) {
            throw new ProductAlreadyInCartException();
        }

        $cartItemId = Uuid::uuid4()->toString();
        
        // UPDATE: Masukkan purchaseType ke Entity CartItem
        $item = new CartItem(
            id: $cartItemId, 
            cartId: $cart->getId(), 
            productId: $productId, 
            quantity: 1, 
            purchaseType: $purchaseType
        );
        
        $this->cartRepository->addItem($item);
        
        $this->logger->log("Added product {$productId} ({$purchaseType}) to cart", 'INFO');
    }
}
