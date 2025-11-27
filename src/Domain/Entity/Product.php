<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class Product
{
    public function __construct(
        private string $id,
        private string $categoryId,
        private string $language,
        private string $type,
        private string $title,
        private ?string $synopsis,
        private string $authorId,
        private ?string $narratorId,
        private ?string $coverImagePath,
        private ?string $profileAudioPath,
        private ?string $sourceFilePath,
        private Money $priceUsd,
        
        // Opsi Sewa
        private bool $canRent = false,
        private ?Money $rentalPriceUsd = null,
        private ?int $rentalDurationDays = null,

        private ?string $tags = null,
        private string $status = 'draft',
        private ?\DateTime $publishedAt = null,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null,

        // PATCH BARU: Data Penutup (Ditaruh di akhir agar backward compatible)
        private ?string $closingText = null,
        private ?string $closingAudioPath = null
    ) {}

    // Getters
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
    public function getSourceFilePath(): ?string { return $this->sourceFilePath; }
    public function getPriceUsd(): Money { return $this->priceUsd; }
    public function canRent(): bool { return $this->canRent; }
    public function getRentalPriceUsd(): ?Money { return $this->rentalPriceUsd; }
    public function getRentalDurationDays(): ?int { return $this->rentalDurationDays; }
    public function getTags(): array { return $this->tags ? explode(',', $this->tags) : []; }
    public function getStatus(): string { return $this->status; }
    public function isPublished(): bool { return $this->status === 'published'; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    
    // Getters Baru
    public function getClosingText(): ?string { return $this->closingText; }
    public function getClosingAudioPath(): ?string { return $this->closingAudioPath; }

    // Setters
    public function setCommercials(Money $price, bool $canRent, ?Money $rentPrice, ?int $rentDuration): void {
        $this->priceUsd = $price;
        $this->canRent = $canRent;
        $this->rentalPriceUsd = $rentPrice;
        $this->rentalDurationDays = $rentDuration;
    }
    public function setSourceFilePath(string $path): void { $this->sourceFilePath = $path; }
    public function setStatus(string $status): void {
        $this->status = $status;
        if ($status === 'published' && $this->publishedAt === null) {
            $this->publishedAt = new \DateTime();
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'language' => $this->language,
            'type' => $this->type,
            'price_usd' => $this->priceUsd->getAmount(),
            'can_rent' => $this->canRent,
            'rental_price_usd' => $this->rentalPriceUsd?->getAmount(),
            'rental_duration_days' => $this->rentalDurationDays,
            'status' => $this->status,
            'closing_text' => $this->closingText, // Expose di array
            'closing_audio_path' => $this->closingAudioPath // Expose di array
        ];
    }
}
