<?php
// file: config/bootstrap.php

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\CartRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\OrderRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Repository\UserLibraryRepositoryInterface; // <-- Penting
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\CurrencyConverterInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\PaymentGatewayInterface;

use Nurtjahjo\StoremgmCA\Infrastructure\Database\ConnectionFactory;
use Nurtjahjo\StoremgmCA\Infrastructure\Logger\FileLogger;
use Nurtjahjo\StoremgmCA\Infrastructure\Repository\ProductPdoRepository;
use Nurtjahjo\StoremgmCA\Infrastructure\Repository\CartPdoRepository;
use Nurtjahjo\StoremgmCA\Infrastructure\Repository\OrderPdoRepository;
use Nurtjahjo\StoremgmCA\Infrastructure\Repository\UserLibraryPdoRepository; // <-- Penting
use Nurtjahjo\StoremgmCA\Infrastructure\Service\LocalStorageService;
use Nurtjahjo\StoremgmCA\Infrastructure\Service\ApiCurrencyConverter;
use Nurtjahjo\StoremgmCA\Infrastructure\Service\MidtransPaymentGateway;

// Load configuration
$appConfig = require __DIR__ . '/app.php';
$dbConfig = require __DIR__ . '/database.php';

try {
    // 1. Inisialisasi Database Connection
    $pdo = ConnectionFactory::create($dbConfig);
    
    // Penentuan Prefix Tabel
    $prefix = $dbConfig['connections']['usermgm-ca']['prefix'] ?? ''; 
    if (isset($dbConfig['connections']['default']['prefix'])) {
        $prefix = $dbConfig['connections']['default']['prefix'];
    } elseif (isset($dbConfig['connections']['storemgm-ca']['prefix'])) {
        $prefix = $dbConfig['connections']['storemgm-ca']['prefix'];
    }

} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database unavailable', 'detail' => $e->getMessage()]);
    exit;
}

// 2. Dependency Container Initialization
$container = [];

// 3. Bind Core Services
// ---------------------

$container[LoggerInterface::class] = new FileLogger($appConfig['storemgm-ca']['log_path']);

$container[StorageServiceInterface::class] = new LocalStorageService(
    $appConfig['storemgm-ca']['storage_path'],
    $container[LoggerInterface::class]
);

$container[CurrencyConverterInterface::class] = new ApiCurrencyConverter();

// Payment Gateway butuh Logger
$container[PaymentGatewayInterface::class] = new MidtransPaymentGateway(
    $container[LoggerInterface::class]
);

// 4. Bind Repositories
// --------------------

$container[ProductRepositoryInterface::class] = new ProductPdoRepository(
    $pdo,
    $prefix . 'products',
    $prefix . 'categories'
);

$container[CartRepositoryInterface::class] = new CartPdoRepository(
    $pdo,
    $prefix . 'carts',
    $prefix . 'cart_items'
);

$container[OrderRepositoryInterface::class] = new OrderPdoRepository(
    $pdo,
    $prefix . 'orders',
    $prefix . 'order_items'
);

// --- PERBAIKAN UTAMA ADA DI SINI ---
// Mendaftarkan UserLibraryRepositoryInterface ke UserLibraryPdoRepository
$container[UserLibraryRepositoryInterface::class] = new UserLibraryPdoRepository(
    $pdo,
    $prefix . 'user_library'
);

// Binding PDO untuk keperluan umum (misal query manual di UseCase Stream)
$container[PDO::class] = $pdo;


// 5. Automatic Dependency Resolver (Reflection)
// ---------------------------------------------
$container['resolve'] = function (string $class) use (&$container) {
    if (isset($container[$class])) {
        return $container[$class];
    }

    $reflector = new ReflectionClass($class);
    if (!$reflector->isInstantiable()) {
        throw new RuntimeException("Cannot instantiate {$class}");
    }

    $constructor = $reflector->getConstructor();
    if (!$constructor) {
        return new $class;
    }

    $dependencies = [];
    foreach ($constructor->getParameters() as $param) {
        $type = $param->getType();
        if ($type && !$type->isBuiltin()) {
            $depClass = $type->getName();
            
            if (isset($container[$depClass])) {
                $dependencies[] = $container[$depClass];
            } else {
                // Rekursif resolve
                $dependencies[] = $container['resolve']($depClass);
            }

        } elseif ($param->isDefaultValueAvailable()) {
            $dependencies[] = $param->getDefaultValue();
        } else {
            throw new RuntimeException("Cannot resolve dependency {$param->getName()} for {$class}");
        }
    }

    return $reflector->newInstanceArgs($dependencies);
};

// Kembalikan container yang sudah lengkap
return $container;
