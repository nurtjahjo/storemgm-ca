<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\AddToCartUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\CheckoutUseCase;
use Nurtjahjo\StoremgmCA\Domain\Exception\StoreManagementException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductAlreadyInCartException;
use Nurtjahjo\StoremgmCA\Domain\Exception\CartIsEmptyException;
use Throwable;

class CartApiController
{
    public function __construct(
        private AddToCartUseCase $addToCartUseCase,
        private CheckoutUseCase $checkoutUseCase
    ) {}

    private function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * POST /api/cart
     * Body: { user_id?, guest_id?, product_id }
     */
    public function addToCart(): void
    {
        $input = $this->getInput();
        
        $userId = $input['user_id'] ?? null;
        $guestId = $input['guest_id'] ?? null;
        $productId = $input['product_id'] ?? '';

        if (empty($productId)) {
            response_json(['error' => 'Product ID is required'], 400);
        }

        try {
            $this->addToCartUseCase->execute($userId, $guestId, $productId);
            response_json(['message' => 'Product added to cart successfully.']);
        } catch (ProductNotFoundException $e) {
            response_json(['error' => $e->getMessage()], 404);
        } catch (ProductAlreadyInCartException $e) {
            response_json(['error' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/checkout
     * Body: { user_id, locale? }
     */
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
        } catch (CartIsEmptyException $e) {
            response_json(['error' => $e->getMessage()], 400);
        } catch (StoreManagementException $e) {
            response_json(['error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
