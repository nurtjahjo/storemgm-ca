<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\CustomerProfile;

interface CustomerProfileRepositoryInterface
{
    public function findByUserId(string $userId): ?CustomerProfile;
    public function save(CustomerProfile $profile): void;
}
