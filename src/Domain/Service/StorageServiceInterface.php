<?php

namespace Nurtjahjo\StoremgmCA\Domain\Service;

interface StorageServiceInterface
{
    /**
     * Mendapatkan konten fisik file (binary/stream) dari penyimpanan KATALOG (Cover/Profil Audio).
     * Digunakan oleh Adapter untuk melayani request API protected catalog.
     * 
     * @param string $relativePath Path relatif (misal: 'covers/42.jpg')
     * @return mixed Stream resource atau string content
     * @throws \RuntimeException Jika file tidak ditemukan
     */
    public function getCatalogAsset(string $relativePath);

    /**
     * Mendapatkan konten fisik file (binary/stream) dari penyimpanan PRIVATE CONTENT (Naskah/Audio Full).
     * Hanya boleh dipanggil setelah otorisasi kepemilikan sukses.
     * 
     * @param string $relativePath Path relatif (misal: 'narrations/42_101.html')
     * @return mixed Stream resource atau string content
     * @throws \RuntimeException Jika file tidak ditemukan
     */
    public function getPrivateContent(string $relativePath);

    /**
     * Mengecek apakah file ada.
     */
    public function exists(string $path, bool $isPrivateContent = false): bool;
    
    /**
     * Menyimpan file baru (digunakan saat migrasi atau upload oleh author nanti).
     */
    public function put(string $path, string $content, bool $isPrivateContent = false): void;
}
