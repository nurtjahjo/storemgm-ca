<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CustomerProfileRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;
use DateTime;

class UpdateCustomerProfileUseCase
{
    public function __construct(
        private CustomerProfileRepositoryInterface $repo
    ) {}

    public function execute(string $userId, ?string $billingAddress, ?string $shippingAddress): void
    {
        // Logika validasi sederhana bisa ditambahkan di sini jika perlu
        
        $profile = new CustomerProfile(
            $userId,
            $billingAddress,
            $shippingAddress,
            new DateTime(), // CreatedAt (akan diabaikan query jika update)
            new DateTime()  // UpdatedAt
        );

        $this->repo->save($profile);
    }
}
