<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\UserLibrary;

interface UserLibraryRepositoryInterface
{
    public function save(UserLibrary $libraryItem): void;
    
    /**
     * Mengembalikan item library jika user memiliki akses VALID (belum expired).
     */
    public function findValidAccess(string $userId, string $productId): ?UserLibrary;
}
