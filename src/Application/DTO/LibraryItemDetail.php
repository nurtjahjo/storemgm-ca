<?php

namespace Nurtjahjo\StoremgmCA\Application\DTO;

class LibraryItemDetail
{
    public function __construct(
        public string $productId,
        public string $title,
        public string $coverImage,
        public string $type, // 'ebook', 'audiobook'
        public string $accessType, // 'owned', 'rented'
        public ?string $expiresAt, // ISO8601 String
        public bool $isExpired,
        public ?int $daysRemaining
    ) {}
}
