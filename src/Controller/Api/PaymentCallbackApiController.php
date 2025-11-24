<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\ProcessPaymentCallbackUseCase;
use Throwable;

class PaymentCallbackApiController
{
    public function __construct(
        private ProcessPaymentCallbackUseCase $useCase
    ) {}

    public function handle(): void
    {
        // Ambil JSON Body dari Payment Gateway (Midtrans mengirim JSON)
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            response_json(['error' => 'Invalid payload'], 400);
        }

        try {
            $this->useCase->execute($input);
            
            // Response OK agar Gateway tidak me-retry
            response_json(['message' => 'Callback processed']);
        } catch (Throwable $e) {
            // Log error di server, tapi tetap return 200 atau 500 tergantung kebijakan Gateway.
            // Midtrans biasanya butuh 200 OK, jika 500 dia akan retry terus.
            // Untuk debug, kita return 500 dulu.
            error_log("Callback Error: " . $e->getMessage());
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
