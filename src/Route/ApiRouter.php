<?php

namespace Nurtjahjo\StoremgmCA\Route;

use Nurtjahjo\StoremgmCA\Controller\Api\ProductApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CartApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CatalogAssetApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\ContentStreamApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\PaymentCallbackApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\LibraryApiController;

class ApiRouter
{
    private static ?array $container = null;

    public static function setContainer(array $container): void
    {
        self::$container = $container;
    }

    private static function getRoutes(): array
    {
        return [
            // System Check
            ['GET',  '/', fn() => response_json(['message' => 'StoreMGM Core API', 'status' => 'OK'])],
            ['GET',  '/api/ping', fn() => response_json(['pong' => true])],

            // Katalog Produk
            ['GET',  '/api/products', [ProductApiController::class, 'index']],
            ['GET',  '/api/products/{id}', [ProductApiController::class, 'show']],

            // Keranjang & Transaksi
            ['GET',  '/api/cart', [CartApiController::class, 'getCart']], // <--- Pastikan ini ada
            ['POST', '/api/cart', [CartApiController::class, 'addToCart']],
            ['POST', '/api/cart/merge', [CartApiController::class, 'merge']],
            ['POST', '/api/checkout', [CartApiController::class, 'checkout']],

            // Webhook Pembayaran
            ['POST', '/api/payment/notification', [PaymentCallbackApiController::class, 'handle']],

            // User Library (Pustaka Saya)
            ['GET', '/api/library', [LibraryApiController::class, 'index']], // <--- Pastikan ini ada

            // Streaming & Aset
            // {filename} akan menangkap string dengan titik, misal 'cover.jpg'
            ['GET', '/api/assets/{type}/{filename}', [CatalogAssetApiController::class, 'getAsset']],
            ['GET', '/api/stream/{productId}/{contentId}/{type}', [ContentStreamApiController::class, 'stream']],
        ];
    }

    public static function resolve(string $method, string $path): mixed
    {
        // Normalisasi path: hapus trailing slash, decode URL
        $path = urldecode(rtrim($path, '/')) ?: '/';

        foreach (self::getRoutes() as [$routeMethod, $routePath, $handler]) {
            // Regex untuk menangkap parameter dinamis {param}
            // Menggunakan (?P<$1>[^/]+) agar cocok dengan segmen URL apa pun kecuali slash
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
                // Filter hasil regex untuk mendapatkan hanya key string (nama parameter)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($handler)) {
                    return $handler;
                }

                if (is_array($handler)) {
                    $controllerClass = $handler[0];
                    $methodName = $handler[1];

                    // Resolve Controller dari Container (Dependency Injection)
                    if (self::$container && isset(self::$container['resolve'])) {
                        $controller = self::$container['resolve']($controllerClass);
                    } else {
                        $controller = new $controllerClass();
                    }

                    return [$controller, $methodName, $params];
                }
            }
        }

        return null;
    }
}
