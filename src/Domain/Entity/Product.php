<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class Product
{
    public function __construct(
        private string $id,
        private string $categoryId,
        private string $language, // 'en' atau 'id'
        private string $type,     // 'ebook' atau 'audiobook'
        private string $title,
        private ?string $synopsis,
        private string $authorId,
        private ?string $narratorId,
        private ?string $coverImagePath,   // Path relatif di storage/app/protected_catalog/covers
        private ?string $profileAudioPath, // Path relatif di storage/app/protected_catalog/profiles
        private Money $priceUsd,
        private ?string $tags = null,
        private string $status = 'draft',
        private ?\DateTime $publishedAt = null,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): string { return $this->id; }
    public function getCategoryId(): string { return $this->categoryId; }
    public function getLanguage(): string { return $this->language; }
    public function getType(): string { return $this->type; }
    public function getTitle(): string { return $this->title; }
    public function getSynopsis(): ?string { return $this->synopsis; }
    public function getAuthorId(): string { return $this->authorId; }
    public function getNarratorId(): ?string { return $this->narratorId; }
    
    public function getCoverImagePath(): ?string { return $this->coverImagePath; }
    public function getProfileAudioPath(): ?string { return $this->profileAudioPath; }
    
    public function getPriceUsd(): Money { return $this->priceUsd; }
    public function getTags(): array 
    { 
        return $this->tags ? explode(',', $this->tags) : []; 
    }
    public function getStatus(): string { return $this->status; }
    public function isPublished(): bool { return $this->status === 'published'; }

    // Setters untuk Update (jika diperlukan oleh UseCase)
    public function setCoverImagePath(?string $path): void { $this->coverImagePath = $path; }
    public function setProfileAudioPath(?string $path): void { $this->profileAudioPath = $path; }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'language' => $this->language,
            'type' => $this->type,
            'price_usd' => $this->priceUsd->getAmount(),
            // Path tidak diekspos langsung ke frontend mentah-mentah di sini, 
            // nanti akan di-resolve menjadi URL API oleh Adapter/Presenter
            'status' => $this->status
        ];
    }
}
