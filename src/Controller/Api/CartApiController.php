<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\AddToCartUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\CheckoutUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\MergeGuestCartUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetCartDetailsUseCase; // Baru
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductAlreadyInCartException;
use Throwable;

class CartApiController
{
    public function __construct(
        private AddToCartUseCase $addToCartUseCase,
        private CheckoutUseCase $checkoutUseCase,
        private MergeGuestCartUseCase $mergeUseCase,
        private GetCartDetailsUseCase $getDetailsUseCase // Baru
    ) {}

    private function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * GET /api/cart
     * Params: user_id OR guest_id
     */
    public function getCart(): void
    {
        $userId = $_GET['user_id'] ?? null;
        $guestId = $_GET['guest_id'] ?? null;

        try {
            $cartDetails = $this->getDetailsUseCase->execute($userId, $guestId);
            
            if (!$cartDetails) {
                // Cart kosong/belum ada, return array kosong
                response_json(['data' => null]);
            } else {
                response_json(['data' => $cartDetails]);
            }
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    public function addToCart(): void
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? null;
        $guestId = $input['guest_id'] ?? null;
        $productId = $input['product_id'] ?? '';
        $purchaseType = $input['purchase_type'] ?? 'buy';

        if (empty($productId)) {
            response_json(['error' => 'Product ID is required'], 400);
        }

        try {
            $this->addToCartUseCase->execute($userId, $guestId, $productId, $purchaseType);
            response_json(['message' => 'Product added to cart successfully.']);
        } catch (ProductNotFoundException $e) {
            response_json(['error' => $e->getMessage()], 404);
        } catch (ProductAlreadyInCartException $e) {
            response_json(['error' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkout(): void
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? '';
        $locale = $input['locale'] ?? 'en';

        if (empty($userId)) {
            response_json(['error' => 'User ID is required for checkout.'], 401);
        }

        try {
            $result = $this->checkoutUseCase->execute($userId, $locale);
            response_json([
                'message' => 'Order created successfully.',
                'data' => $result
            ]);
        } catch (StoreManagementException $e) {
            response_json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    public function merge(): void
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? '';
        $guestId = $input['guest_id'] ?? '';

        if (empty($userId) || empty($guestId)) {
            response_json(['error' => 'User ID and Guest ID are required.'], 400);
        }

        try {
            $this->mergeUseCase->execute($userId, $guestId);
            response_json(['message' => 'Cart merged successfully.']);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
