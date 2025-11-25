<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\Cart;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class MergeGuestCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(string $userId, string $guestCartId): void
    {
        $guestCart = $this->cartRepository->findByGuestId($guestCartId);

        // Jika tidak ada keranjang tamu, tidak ada yang perlu digabung
        if (!$guestCart || empty($guestCart->getItems())) {
            return;
        }

        $userCart = $this->cartRepository->findByUserId($userId);

        // Skenario A: User belum punya keranjang
        // Cukup ubah kepemilikan keranjang tamu menjadi milik user
        if (!$userCart) {
            // Kita perlu method khusus di repo untuk ini agar efisien, 
            // atau kita update manual lewat save() dengan asumsi repo menangani update logic
            // Tapi karena entity Cart immutable di properti ID/User, 
            // cara terbersih adalah memindahkan item atau update low-level.
            
            // Pendekatan pragmatis: Update user_id di guest cart
            // (Memerlukan method khusus di Repo untuk update ownership akan lebih bersih)
            $this->cartRepository->transferOwnership($guestCart->getId(), $userId);
            $this->logger->log("Transferred guest cart {$guestCart->getId()} to user {$userId}", 'INFO');
            return;
        }

        // Skenario B: User sudah punya keranjang
        // Pindahkan item satu per satu
        foreach ($guestCart->getItems() as $guestItem) {
            // Cek apakah produk sudah ada di keranjang user (Logic produk digital: Max 1)
            if (!$userCart->hasProduct($guestItem->getProductId())) {
                // Re-assign cart ID item ke keranjang user
                // Kita buat item baru atau update yang lama
                // Di sini kita gunakan addItem ke userCart
                // ID item baru digenerate atau pakai yang lama? Lebih aman buat baru/pindah.
                
                // Kita panggil repo untuk memindahkan item
                $this->cartRepository->moveItemToCart($guestItem->getId(), $userCart->getId());
            }
        }

        // Hapus keranjang tamu setelah dikosongkan/dipindahkan
        $this->cartRepository->delete($guestCart->getId());
        $this->logger->log("Merged guest cart {$guestCartId} into user cart {$userCart->getId()}", 'INFO');
    }
}
