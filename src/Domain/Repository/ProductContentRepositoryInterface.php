<?php

namespace Nurtjahjo\StoremgmCA\Domain\Repository;

use Nurtjahjo\StoremgmCA\Domain\Entity\ProductContent;

interface ProductContentRepositoryInterface
{
    public function save(ProductContent $content): void;
    public function findById(string $id): ?ProductContent;
    public function countByProductId(string $productId): int;
}
