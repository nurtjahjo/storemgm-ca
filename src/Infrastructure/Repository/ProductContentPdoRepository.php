<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use Nurtjahjo\StoremgmCA\Domain\Entity\ProductContent;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductContentRepositoryInterface;

class ProductContentPdoRepository implements ProductContentRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $table = 'storemgm_product_contents'
    ) {}

    public function save(ProductContent $content): void
    {
        $sql = "INSERT INTO {$this->table} 
            (id, product_id, title, chapter_order, content_text_path, content_audio_path, word_count, duration_seconds, status, created_at, updated_at)
            VALUES 
            (:id, :pid, :title, :ord, :txt, :aud, :wc, :dur, :stat, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            chapter_order = VALUES(chapter_order),
            content_text_path = VALUES(content_text_path),
            content_audio_path = VALUES(content_audio_path),
            status = VALUES(status),
            updated_at = NOW()";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $content->getId(),
            ':pid' => $content->getProductId(),
            ':title' => $content->getTitle(),
            ':ord' => $content->getChapterOrder(),
            ':txt' => $content->getContentTextPath(),
            ':aud' => $content->getContentAudioPath(),
            ':wc' => $content->getWordCount(),
            ':dur' => $content->getDurationSeconds(),
            ':stat' => $content->getStatus()
        ]);
    }

    public function findById(string $id): ?ProductContent
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;

        return new ProductContent(
            $row['id'], $row['product_id'], $row['title'], 
            (int)$row['chapter_order'], $row['content_text_path'], $row['content_audio_path'],
            (int)$row['word_count'], (int)$row['duration_seconds'], $row['status']
        );
    }

    public function countByProductId(string $productId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE product_id = :pid AND status = 'approved'");
        $stmt->execute([':pid' => $productId]);
        return (int)$stmt->fetchColumn();
    }
}
