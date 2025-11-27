<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Repository;

use PDO;
use DateTime;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Application\DTO\PaginatedResult;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class ProductPdoRepository implements ProductRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private string $table = 'storemgm_products',
        private string $catTable = 'storemgm_categories'
    ) {}

    public function save(Product $product): void
    {
        $sql = "INSERT INTO {$this->table} 
                (id, category_id, language, type, title, synopsis, author_id, narrator_id, 
                 cover_image_path, profile_audio_path, source_file_path, closing_text, closing_audio_path,
                 price_usd, can_rent, rental_price_usd, rental_duration_days,
                 tags, status, created_at, updated_at, published_at)
                VALUES 
                (:id, :category_id, :language, :type, :title, :synopsis, :author_id, :narrator_id, 
                 :cover_path, :profile_path, :source_path, :close_txt, :close_aud,
                 :price, :can_rent, :rent_price, :rent_days,
                 :tags, :status, :created_at, :updated_at, :published_at)
                ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                title = VALUES(title),
                synopsis = VALUES(synopsis),
                cover_image_path = VALUES(cover_image_path),
                profile_audio_path = VALUES(profile_audio_path),
                source_file_path = VALUES(source_file_path),
                closing_text = VALUES(closing_text),
                closing_audio_path = VALUES(closing_audio_path),
                price_usd = VALUES(price_usd),
                can_rent = VALUES(can_rent),
                rental_price_usd = VALUES(rental_price_usd),
                rental_duration_days = VALUES(rental_duration_days),
                tags = VALUES(tags),
                status = VALUES(status),
                updated_at = VALUES(updated_at),
                published_at = VALUES(published_at)";

        $stmt = $this->pdo->prepare($sql);
        
        $tags = $product->getTags();
        $tagsString = !empty($tags) ? implode(',', $tags) : null;

        $stmt->execute([
            ':id' => $product->getId(),
            ':category_id' => $product->getCategoryId(),
            ':language' => $product->getLanguage(),
            ':type' => $product->getType(),
            ':title' => $product->getTitle(),
            ':synopsis' => $product->getSynopsis(),
            ':author_id' => $product->getAuthorId(),
            ':narrator_id' => $product->getNarratorId(),
            ':cover_path' => $product->getCoverImagePath(),
            ':profile_path' => $product->getProfileAudioPath(),
            ':source_path' => $product->getSourceFilePath(),
            ':close_txt' => $product->getClosingText(),
            ':close_aud' => $product->getClosingAudioPath(),
            ':price' => $product->getPriceUsd()->getAmount(),
            ':can_rent' => (int) $product->canRent(),
            ':rent_price' => $product->getRentalPriceUsd()?->getAmount(),
            ':rent_days' => $product->getRentalDurationDays(),
            ':tags' => $tagsString,
            ':status' => $product->getStatus(),
            ':created_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ':updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
            ':published_at' => $product->isPublished() ? (new DateTime())->format('Y-m-d H:i:s') : null,
        ]);
    }

    public function findById(string $id): ?Product
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function findWithPagination(
        string $language,
        int $page,
        int $perPage,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $categoryId = null,
        ?string $searchQuery = null
    ): PaginatedResult {
        $whereClauses = ["language = :language"];
        $params = [':language' => $language];

        if ($categoryId) {
            $whereClauses[] = "category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        if ($searchQuery) {
            $whereClauses[] = "(title LIKE :search_title OR tags LIKE :search_tags)";
            $params[':search_title'] = "%{$searchQuery}%";
            $params[':search_tags'] = "%{$searchQuery}%";
        }

        $whereSql = implode(' AND ', $whereClauses);
        $countSql = "SELECT COUNT(id) FROM {$this->table} WHERE {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        
        $allowedSorts = ['created_at', 'price_usd', 'title', 'published_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $dataSql = "SELECT * FROM {$this->table} 
                    WHERE {$whereSql} 
                    ORDER BY {$sortBy} {$sortDir} 
                    LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($dataSql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = array_map([$this, 'mapRowToEntity'], $rows);

        return new PaginatedResult($products, $total, $page, $perPage);
    }

    public function findByOriginalId(int $originalId): ?Product
    {
        return null;
    }

    private function mapRowToEntity(array $row): Product
    {
        return new Product(
            id: $row['id'],
            categoryId: $row['category_id'],
            language: $row['language'],
            type: $row['type'],
            title: $row['title'],
            synopsis: $row['synopsis'],
            authorId: $row['author_id'],
            narratorId: $row['narrator_id'],
            coverImagePath: $row['cover_image_path'],
            profileAudioPath: $row['profile_audio_path'],
            sourceFilePath: $row['source_file_path'] ?? null, 
            priceUsd: Money::fromDecimal($row['price_usd']),
            canRent: (bool)($row['can_rent'] ?? false),
            rentalPriceUsd: isset($row['rental_price_usd']) ? Money::fromDecimal($row['rental_price_usd']) : null,
            rentalDurationDays: isset($row['rental_duration_days']) ? (int)$row['rental_duration_days'] : null,
            tags: $row['tags'],
            status: $row['status'],
            publishedAt: $row['published_at'] ? new DateTime($row['published_at']) : null,
            createdAt: new DateTime($row['created_at']),
            updatedAt: new DateTime($row['updated_at']),
            // Mapping kolom baru
            closingText: $row['closing_text'] ?? null,
            closingAudioPath: $row['closing_audio_path'] ?? null
        );
    }
}
