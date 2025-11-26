<?php

namespace Nurtjahjo\StoremgmCA\Domain\Entity;

class ProductContent
{
    public function __construct(
        private string $id,
        private string $productId,
        private string $title,
        private int $chapterOrder,
        private ?string $contentTextPath,
        private ?string $contentAudioPath,
        private int $wordCount = 0,
        private int $durationSeconds = 0,
        private string $status = 'draft'
    ) {}

    public function getId(): string { return $this->id; }
    public function getProductId(): string { return $this->productId; }
    public function getTitle(): string { return $this->title; }
    public function getChapterOrder(): int { return $this->chapterOrder; }
    public function getContentTextPath(): ?string { return $this->contentTextPath; }
    public function getContentAudioPath(): ?string { return $this->contentAudioPath; }
    public function getWordCount(): int { return $this->wordCount; }
    public function getDurationSeconds(): int { return $this->durationSeconds; }
    public function getStatus(): string { return $this->status; }
}
