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
                (id, category_id, language, type, title, synopsis, author_id, narrator_id, cover_image_path, profile_audio_path, price_usd, tags, status, created_at, updated_at, published_at)
                VALUES 
                (:id, :category_id, :language, :type, :title, :synopsis, :author_id, :narrator_id, :cover_image_path, :profile_audio_path, :price_usd, :tags, :status, :created_at, :updated_at, :published_at)
                ON DUPLICATE KEY UPDATE
                category_id = VALUES(category_id),
                title = VALUES(title),
                synopsis = VALUES(synopsis),
                cover_image_path = VALUES(cover_image_path),
                profile_audio_path = VALUES(profile_audio_path),
                price_usd = VALUES(price_usd),
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
            ':cover_image_path' => $product->getCoverImagePath(),
            ':profile_audio_path' => $product->getProfileAudioPath(),
            ':price_usd' => $product->getPriceUsd()->getAmount(),
            ':tags' => $tagsString,
            ':status' => $product->getStatus(),
            ':created_at' => (new DateTime())->format('Y-m-d H:i:s'), // Biasanya diambil dari entity jika ada getter-nya
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
        // 1. Bangun Query Dasar
        $whereClauses = ["language = :language"];
        $params = [':language' => $language];

        // Filter Kategori
        if ($categoryId) {
            $whereClauses[] = "category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        // Filter Pencarian (Title atau Tags)
        if ($searchQuery) {
            $whereClauses[] = "(title LIKE :search OR tags LIKE :search)";
            $params[':search'] = "%{$searchQuery}%";
        }

        // Filter Status (Hanya tampilkan yang published untuk publik, 
        // logic ini bisa disesuaikan jika untuk admin panel)
        // $whereClauses[] = "status = 'published'"; 

        $whereSql = implode(' AND ', $whereClauses);

        // 2. Hitung Total (untuk paginasi)
        $countSql = "SELECT COUNT(id) FROM {$this->table} WHERE {$whereSql}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // 3. Ambil Data Halaman Ini
        $offset = ($page - 1) * $perPage;
        
        // Whitelist kolom sorting untuk mencegah SQL Injection
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
        
        // Bind parameter WHERE
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        // Bind parameter LIMIT & OFFSET (harus integer)
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Map ke Entity
        $products = array_map([$this, 'mapRowToEntity'], $rows);

        return new PaginatedResult($products, $total, $page, $perPage);
    }

    public function findByOriginalId(int $originalId): ?Product
    {
        // Metode ini memerlukan kolom 'original_id' di tabel jika ingin diimplementasikan,
        // atau tabel mapping terpisah. Untuk saat ini kita kembalikan null atau
        // bisa diimplementasikan nanti saat migrasi.
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
            priceUsd: Money::fromDecimal($row['price_usd']),
            tags: $row['tags'],
            status: $row['status'],
            publishedAt: $row['published_at'] ? new DateTime($row['published_at']) : null,
            createdAt: new DateTime($row['created_at']),
            updatedAt: new DateTime($row['updated_at'])
        );
    }
}
