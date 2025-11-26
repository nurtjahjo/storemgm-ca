<?php

namespace Nurtjahjo\StoremgmCA\Application\UseCase;

use Nurtjahjo\StoremgmCA\Domain\Repository\CustomerProfileRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;

class GetCustomerProfileUseCase
{
    public function __construct(
        private CustomerProfileRepositoryInterface $repo
    ) {}

    public function execute(string $userId): CustomerProfile
    {
        $profile = $this->repo->findByUserId($userId);
        
        // Jika belum ada, kembalikan objek kosong (default) daripada null/error
        if (!$profile) {
            return new CustomerProfile($userId, null, null);
        }
        
        return $profile;
    }
}
