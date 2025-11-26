<?php

namespace Nurtjahjo\StoremgmCA\Controller\Api;

use Nurtjahjo\StoremgmCA\Application\UseCase\ListProductsUseCase;
use Nurtjahjo\StoremgmCA\Application\UseCase\GetProductDetailsUseCase;
use Nurtjahjo\StoremgmCA\Domain\Exception\ProductNotFoundException;
use Throwable;

class ProductApiController
{
    public function __construct(
        private ListProductsUseCase $listProductsUseCase,
        private GetProductDetailsUseCase $getProductDetailsUseCase
    ) {}

    // Helper untuk mengambil input JSON (jika perlu)
    private function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * GET /api/products
     * Query Params: lang, page, per_page, sort, dir, cat, q
     */
    public function index(): void
    {
        try {
            // Ambil parameter dari Query String
            $lang = $_GET['lang'] ?? 'id'; // Default ID
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $sortBy = $_GET['sort'] ?? 'created_at';
            $sortDir = $_GET['dir'] ?? 'desc';
            $catId = $_GET['cat'] ?? null;
            $search = $_GET['q'] ?? null;

            $result = $this->listProductsUseCase->execute(
                $lang, $page, $perPage, $sortBy, $sortDir, $catId, $search
            );

            // Konversi Entity Product ke Array untuk JSON
            $data = array_map(fn($p) => $p->toArray(), $result->data);

            response_json([
                'data' => $data,
                'meta' => [
                    'total' => $result->total,
                    'page' => $result->currentPage,
                    'per_page' => $result->perPage
                ]
            ]);

        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/products/{id}
     */
    public function show(string $id): void
    {
        try {
            $product = $this->getProductDetailsUseCase->execute($id);
            response_json($product->toArray());
        } catch (ProductNotFoundException $e) {
            response_json(['error' => $e->getMessage()], 404);
        } catch (Throwable $e) {
            response_json(['error' => $e->getMessage()], 500);
        }
    }
}
