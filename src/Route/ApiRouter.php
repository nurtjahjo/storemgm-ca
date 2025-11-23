<?php

namespace Nurtjahjo\StoremgmCA\Route;

use Nurtjahjo\StoremgmCA\Controller\Api\ProductApiController;
use Nurtjahjo\StoremgmCA\Controller\Api\CartApiController;

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
            // Basic
            ['GET',  '/', fn() => response_json(['message' => 'StoreMGM Core API', 'status' => 'OK'])],
            ['GET',  '/api/ping', fn() => response_json(['pong' => true])],

            // Products
            ['GET',  '/api/products', [ProductApiController::class, 'index']],
            ['GET',  '/api/products/{id}', [ProductApiController::class, 'show']],

            // Cart & Checkout
            ['POST', '/api/cart', [CartApiController::class, 'addToCart']],
            ['POST', '/api/checkout', [CartApiController::class, 'checkout']],
        ];
    }

    public static function resolve(string $method, string $path): mixed
    {
        // Normalisasi path: hapus trailing slash, decode URL
        $path = urldecode(rtrim($path, '/')) ?: '/';

        foreach (self::getRoutes() as [$routeMethod, $routePath, $handler]) {
            // Ubah parameter {id} menjadi regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
                // Ambil parameter (misal id)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Jika handler adalah closure/fungsi langsung
                if (is_callable($handler)) {
                    return $handler;
                }

                // Jika handler adalah [Controller::class, 'method']
                if (is_array($handler)) {
                    $controllerClass = $handler[0];
                    $methodName = $handler[1];

                    // Resolve Controller dari Container (Dependency Injection otomatis)
                    if (self::$container && isset(self::$container['resolve'])) {
                        $controller = self::$container['resolve']($controllerClass);
                    } else {
                        // Fallback manual jika container error (seharusnya tidak terjadi)
                        $controller = new $controllerClass();
                    }

                    return [$controller, $methodName, $params];
                }
            }
        }

        return null;
    }
}
