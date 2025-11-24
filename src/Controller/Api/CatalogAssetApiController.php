<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\GetCatalogAssetUseCase;
use Throwable;

class CatalogAssetApiController
{
    public function __construct(
        private GetCatalogAssetUseCase $useCase
    ) {}

    public function getAsset(string $type, string $filename): void
    {
        try {
            $stream = $this->useCase->execute($type, $filename);

            // Deteksi MIME Type sederhana
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'mp3' => 'audio/mpeg',
                default => 'application/octet-stream'
            };

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . fstat($stream)['size']);
            
            // Output stream ke browser
            fpassthru($stream);
            exit;

        } catch (Throwable $e) {
            http_response_code(404);
            echo "File not found.";
        }
    }
}
