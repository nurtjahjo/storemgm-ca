<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserOrdersUseCase;
use Throwable;

class OrderApiController
{
    public function __construct(
        private GetUserOrdersUseCase $useCase
    ) {}

    // GET /api/orders
    public function index(): void
    {
        $userId = $_GET['user_id'] ?? '';
        if (!$userId) { response_json(['error' => 'Unauthorized'], 401); }

        try {
            $orders = $this->useCase->execute($userId);
            response_json(['data' => $orders]);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
