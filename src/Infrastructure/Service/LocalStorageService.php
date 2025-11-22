<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Service;

use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use RuntimeException;

class LocalStorageService implements StorageServiceInterface
{
    private string $catalogBasePath;
    private string $privateContentBasePath;

    /**
     * @param string $storageRootPath Path absolut ke folder 'storage/app'
     */
    public function __construct(
        string $storageRootPath,
        private LoggerInterface $logger
    ) {
        // Normalisasi path dan hapus trailing slash
        $root = rtrim($storageRootPath, '/\\');
        
        $this->catalogBasePath = $root . '/protected_catalog';
        $this->privateContentBasePath = $root . '/private_content';

        // Pastikan folder dasar ada, jika tidak coba buat (untuk dev environment)
        if (!is_dir($this->catalogBasePath)) @mkdir($this->catalogBasePath, 0755, true);
        if (!is_dir($this->privateContentBasePath)) @mkdir($this->privateContentBasePath, 0755, true);
    }

    public function getCatalogAsset(string $relativePath)
    {
        return $this->getFileStream($this->catalogBasePath, $relativePath);
    }

    public function getPrivateContent(string $relativePath)
    {
        return $this->getFileStream($this->privateContentBasePath, $relativePath);
    }

    public function exists(string $path, bool $isPrivateContent = false): bool
    {
        $basePath = $isPrivateContent ? $this->privateContentBasePath : $this->catalogBasePath;
        $fullPath = $this->resolvePath($basePath, $path);
        return $fullPath && file_exists($fullPath);
    }

    public function put(string $path, string $content, bool $isPrivateContent = false): void
    {
        $basePath = $isPrivateContent ? $this->privateContentBasePath : $this->catalogBasePath;
        
        // Tidak menggunakan resolvePath di sini karena file mungkin belum ada
        // Kita hanya perlu memastikan direktori tujuannya aman dan ada
        $targetPath = $basePath . '/' . ltrim($path, '/\\');
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (file_put_contents($targetPath, $content) === false) {
            $this->logger->log("Failed to write file to storage: $targetPath");
            throw new RuntimeException("Could not save file.");
        }
    }

    /**
     * Membuka stream file dengan perlindungan Path Traversal.
     * Mengembalikan resource stream (fopen).
     */
    private function getFileStream(string $basePath, string $relativePath)
    {
        $fullPath = $this->resolvePath($basePath, $relativePath);

        if (!$fullPath || !file_exists($fullPath)) {
            $this->logger->log("File not found or access denied: $relativePath in $basePath");
            throw new RuntimeException("File not found.");
        }

        $stream = fopen($fullPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Could not open file stream.");
        }

        return $stream;
    }

    /**
     * Menyelesaikan path dan memastikan file berada di dalam direktori yang diizinkan.
     * Mencegah serangan Path Traversal (misal: ../../etc/passwd).
     */
    private function resolvePath(string $basePath, string $relativePath): ?string
    {
        // Gabungkan path
        $path = $basePath . '/' . ltrim($relativePath, '/\\');
        
        // Resolve realpath (ini akan mengembalikan false jika file tidak ada)
        // Untuk keamanan pengecekan direktori, kita cek realpath direktorinya jika file belum ada (kasus write)
        // Tapi untuk read (fungsi ini), realpath file harus ada.
        $realPath = realpath($path);

        if ($realPath === false) {
            return null;
        }

        // Pastikan realPath dimulai dengan basePath yang diizinkan
        if (!str_starts_with($realPath, realpath($basePath))) {
            return null;
        }

        return $realPath;
    }
}
