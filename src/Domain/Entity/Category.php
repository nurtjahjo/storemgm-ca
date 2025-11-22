<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

class Category
{
    public function __construct(
        private string $id,
        private string $language, // 'en' atau 'id'
        private string $name,
        private string $slug,
        private ?string $description = null,
        private int $displayOrder = 0,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {}

    // Getters
    public function getId(): string { return $this->id; }
    public function getLanguage(): string { return $this->language; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
    
    // Domain Logic sederhana
    public function isTranslationOf(Category $otherCategory): bool
    {
        // Logika sederhana: jika slug dasarnya mirip (bisa dikembangkan nanti)
        // Untuk saat ini, kita anggap kategori berbeda bahasa adalah entitas terpisah
        return false; 
    }
}
