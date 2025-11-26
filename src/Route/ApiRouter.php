<?php

namespace Nurtjahjo\StoremgmCA\Route;

use Nurtjahjo\StoremgmCA\Controller\Api\ProductApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CartApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CatalogAssetApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\ContentStreamApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\PaymentCallbackApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\LibraryApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CustomerProfileApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\OrderApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\WishlistApiController; // NEW

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
            ['GET',  '/', fn() => response_json(['message' => 'StoreMGM Core API', 'status' => 'OK'])],
            ['GET',  '/api/ping', fn() => response_json(['pong' => true])],

            // Products
            ['GET',  '/api/products', [ProductApiController::class, 'index']],
            ['GET',  '/api/products/{id}', [ProductApiController::class, 'show']],

            // Cart & Checkout
            ['GET',  '/api/cart', [CartApiController::class, 'getCart']],
            ['POST', '/api/cart', [CartApiController::class, 'addToCart']],
            ['POST', '/api/cart/merge', [CartApiController::class, 'merge']],
            ['POST', '/api/checkout', [CartApiController::class, 'checkout']],

            // Payment Webhook
            ['POST', '/api/payment/notification', [PaymentCallbackApiController::class, 'handle']],

            // User Features
            ['GET', '/api/library', [LibraryApiController::class, 'index']],
            ['GET', '/api/customer/profile', [CustomerProfileApiController::class, 'show']],
            ['PUT', '/api/customer/profile', [CustomerProfileApiController::class, 'update']],
            ['GET', '/api/orders', [OrderApiController::class, 'index']],

            // Wishlist (NEW)
            ['GET',    '/api/wishlist', [WishlistApiController::class, 'index']],
            ['POST',   '/api/wishlist', [WishlistApiController::class, 'add']],
            ['DELETE', '/api/wishlist', [WishlistApiController::class, 'remove']],

            // Assets & Stream
            ['GET', '/api/assets/{type}/{filename}', [CatalogAssetApiController::class, 'getAsset']],
            ['GET', '/api/stream/{productId}/{contentId}/{type}', [ContentStreamApiController::class, 'stream']],
        ];
    }

    public static function resolve(string $method, string $path): mixed
    {
        $path = urldecode(rtrim($path, '/')) ?: '/';

        foreach (self::getRoutes() as [$routeMethod, $routePath, $handler]) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($handler)) {
                    return $handler;
                }

                if (is_array($handler)) {
                    $controllerClass = $handler[0];
                    $methodName = $handler[1];

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
