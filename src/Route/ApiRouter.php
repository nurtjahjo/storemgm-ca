<?php

namespace Nurtjahjo\StoremgmCA\Route;

use Nurtjahjo\StoremgmCA\Controller\Api\ProductApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CartApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CatalogAssetApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\ContentStreamApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\PaymentCallbackApiController;

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

            ['GET',  '/api/products', [ProductApiController::class, 'index']],
            ['GET',  '/api/products/{id}', [ProductApiController::class, 'show']],

            ['POST', '/api/cart', [CartApiController::class, 'addToCart']],
            ['POST', '/api/checkout', [CartApiController::class, 'checkout']],

            ['GET', '/api/assets/{type}/{filename}', [CatalogAssetApiController::class, 'getAsset']],
            ['GET', '/api/stream/{productId}/{contentId}/{type}', [ContentStreamApiController::class, 'stream']],

            // NEW: Payment Callback Route
            ['POST', '/api/payment/notification', [PaymentCallbackApiController::class, 'handle']],
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
