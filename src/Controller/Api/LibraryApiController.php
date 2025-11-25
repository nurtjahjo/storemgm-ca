<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserLibraryUseCase;
use Throwable;

class LibraryApiController
{
    public function __construct(
        private GetUserLibraryUseCase $useCase
    ) {}

    public function index(): void
    {
        $userId = $_GET['user_id'] ?? '';
        if (empty($userId)) {
            response_json(['error' => 'User ID required'], 401);
        }

        try {
            $items = $this->useCase->execute($userId);
            response_json(['data' => $items]);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
