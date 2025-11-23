<?php

use Nurtjahjo\StoremgmCA\Route\ApiRouter;

// 1. Autoload
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Support/functions.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Bootstrap (Load Container)
$container = require __DIR__ . '/../config/bootstrap.php';

// 3. Setup Router
ApiRouter::setContainer($container);

// 4. Capture Request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 5. Resolve Route
$route = ApiRouter::resolve($method, $path);

try {
    if (!$route) {
        response_json(['error' => 'Route not found'], 404);
    }

    if (is_callable($route)) {
        $route();
    } elseif (is_array($route)) {
        [$controller, $methodName, $params] = $route;
        $controller->{$methodName}(...array_values($params));
    }

} catch (Throwable $e) {
    response_json([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString() // Hapus ini di production
    ], 500);
}
