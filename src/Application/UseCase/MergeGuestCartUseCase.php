<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Exception;

class MergeGuestCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private LoggerInterface $logger
    ) {}

    public function execute(string $userId, string $guestCartId): void
    {
        $guestCart = $this->cartRepository->findByGuestId($guestCartId);

        if (!$guestCart || empty($guestCart->getItems())) {
            return;
        }

        $userCart = $this->cartRepository->findByUserId($userId);

        // Skenario A: User belum punya keranjang -> Transfer ownership
        if (!$userCart) {
            try {
                $this->cartRepository->transferOwnership($guestCart->getId(), $userId);
                $this->logger->log("Transferred guest cart {$guestCart->getId()} to user {$userId}", 'INFO');
            } catch (Exception $e) {
                $this->logger->log("Failed to transfer cart ownership: " . $e->getMessage(), 'ERROR');
                // Jangan throw error ke user, biarkan proses lanjut (fail safe)
            }
            return;
        }

        // Skenario B: User sudah punya keranjang -> Merge items
        foreach ($guestCart->getItems() as $guestItem) {
            // Jika user sudah punya produk itu, skip (jangan double)
            if ($userCart->hasProduct($guestItem->getProductId())) {
                continue;
            }

            try {
                // Coba pindahkan item
                $this->cartRepository->moveItemToCart($guestItem->getId(), $userCart->getId());
            } catch (Exception $e) {
                // Tangkap error duplicate entry (race condition atau unique constraint)
                // Log dan lanjutkan ke item berikutnya
                $this->logger->log("Merge conflict for item {$guestItem->getId()}: " . $e->getMessage(), 'WARNING');
            }
        }

        // Bersihkan sisa keranjang tamu
        $this->cartRepository->delete($guestCart->getId());
        $this->logger->log("Merged guest cart {$guestCartId} into user cart {$userCart->getId()}", 'INFO');
    }
}
