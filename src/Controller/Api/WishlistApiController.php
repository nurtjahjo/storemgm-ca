<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\AddToWishlistUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\RemoveFromWishlistUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetUserWishlistUseCase;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Throwable;

class WishlistApiController
{
    public function __construct(
        private AddToWishlistUseCase $addUseCase,
        private RemoveFromWishlistUseCase $removeUseCase,
        private GetUserWishlistUseCase $getUseCase
    ) {}

    private function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // GET /api/wishlist?user_id=...
    public function index(): void
    {
        $userId = $_GET['user_id'] ?? '';
        if (!$userId) { response_json(['error' => 'Unauthorized'], 401); }

        try {
            $products = $this->getUseCase->execute($userId);
            response_json(['data' => $products]);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /api/wishlist
    public function add(): void
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? '';
        $productId = $input['product_id'] ?? '';

        if (!$userId || !$productId) {
            response_json(['error' => 'User ID and Product ID required'], 400);
        }

        try {
            $this->addUseCase->execute($userId, $productId);
            response_json(['message' => 'Added to wishlist']);
        } catch (ProductNotFoundException $e) {
            response_json(['error' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/wishlist?user_id=...&product_id=...
    // (Menggunakan Query Param untuk DELETE agar simpel di standalone router)
    public function remove(): void
    {
        $userId = $_GET['user_id'] ?? '';
        $productId = $_GET['product_id'] ?? '';

        if (!$userId || !$productId) {
            response_json(['error' => 'User ID and Product ID required'], 400);
        }

        try {
            $this->removeUseCase->execute($userId, $productId);
            response_json(['message' => 'Removed from wishlist']);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
