<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\StreamProductContentUseCase;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Throwable;

class ContentStreamApiController
{
    public function __construct(
        private StreamProductContentUseCase $useCase
    ) {}

    public function stream(string $productId, string $contentId, string $type): void
    {
        // TODO: Di Adapter Laravel nanti, User ID diambil dari $request->user()->id
        // Di Standalone ini, kita simulasi ambil dari Query Param untuk testing
        $userId = $_GET['user_id'] ?? ''; 

        if (empty($userId)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. User ID required.']);
            exit;
        }

        // Jika contentId string 'source', berarti minta file utuh
        $realContentId = ($contentId === 'source') ? null : $contentId;
        $realType = ($contentId === 'source') ? 'source' : $type;

        try {
            $stream = $this->useCase->execute($userId, $productId, $realContentId, $realType);

            // Set Headers untuk Streaming
            $mime = match ($realType) {
                'text' => 'text/html',
                'audio' => 'audio/mpeg',
                'source' => 'application/epub+zip', // Asumsi EPUB
                default => 'application/octet-stream'
            };

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . fstat($stream)['size']);
            header('Accept-Ranges: bytes'); // Mendukung seek audio player

            fpassthru($stream);
            exit;

        } catch (StoreManagementException $e) {
            http_response_code($e->getCode() ?: 403);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
