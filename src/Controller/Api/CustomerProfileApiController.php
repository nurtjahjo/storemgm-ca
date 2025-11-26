<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\GetCustomerProfileUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\UpdateCustomerProfileUseCase;
use Throwable;

class CustomerProfileApiController
{
    public function __construct(
        private GetCustomerProfileUseCase $getUseCase,
        private UpdateCustomerProfileUseCase $updateUseCase
    ) {}

    private function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // GET /api/customer/profile
    public function show(): void
    {
        $userId = $_GET['user_id'] ?? '';
        if (!$userId) { response_json(['error' => 'Unauthorized'], 401); }

        try {
            $profile = $this->getUseCase->execute($userId);
            response_json(['data' => $profile->toArray()]);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/customer/profile
    public function update(): void
    {
        $input = $this->getInput();
        $userId = $input['user_id'] ?? '';
        if (!$userId) { response_json(['error' => 'Unauthorized'], 401); }

        try {
            $this->updateUseCase->execute(
                $userId, 
                $input['billing_address'] ?? null,
                $input['shipping_address'] ?? null
            );
            response_json(['message' => 'Profile updated successfully']);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
