<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;

interface UserLibraryRepositoryInterface
{
    public function save(UserLibrary $libraryItem): void;
    
    public function findValidAccess(string $userId, string $productId): ?UserLibrary;
    
    /**
     * Mengambil semua item di library user (termasuk yang expired).
     * @return UserLibrary[]
     */
    public function findByUserId(string $userId): array;
}
