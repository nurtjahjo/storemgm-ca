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

    /**
     * Menambahkan produk ke keranjang.
     * 
     * @param string|null $userId ID User jika login
     * @param string|null $guestCartId ID Guest Cart jika belum login
     * @param string $productId ID Produk yang akan dibeli
     */
    public function execute(?string $userId, ?string $guestCartId, string $productId): void
    {
        // 1. Validasi Produk (Pastikan produk ada)
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new ProductNotFoundException("Product ID {$productId} not found.");
        }

        // 2. Tentukan/Cari Keranjang
        $cart = null;
        if ($userId) {
            $cart = $this->cartRepository->findByUserId($userId);
        } elseif ($guestCartId) {
            $cart = $this->cartRepository->findByGuestId($guestCartId);
        } else {
            // Kasus langka: tidak ada user ID dan tidak ada guest ID yang dikirim frontend
            // Seharusnya frontend meng-generate UUID guest jika user belum login.
            // Kita bisa throw exception atau generate baru di sini.
            $guestCartId = Uuid::uuid4()->toString();
        }

        // 3. Jika keranjang belum ada, Buat Baru
        if (!$cart) {
            $cartId = Uuid::uuid4()->toString();
            $cart = new Cart($cartId, $userId, $guestCartId);
            $this->cartRepository->save($cart);
            $this->logger->log("Created new cart: {$cartId} (User: " . ($userId ?? 'Guest') . ")", 'INFO');
        }

        // 4. Cek apakah produk sudah ada di keranjang (Produk Digital = Max 1)
        if ($cart->hasProduct($productId)) {
            throw new ProductAlreadyInCartException();
        }

        // 5. Tambahkan Item
        $cartItemId = Uuid::uuid4()->toString();
        $item = new CartItem($cartItemId, $cart->getId(), $productId, 1);
        
        $this->cartRepository->addItem($item);
        
        $this->logger->log("Added product {$productId} to cart {$cart->getId()}", 'INFO');
    }
}
