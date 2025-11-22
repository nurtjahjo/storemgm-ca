<?php
// file: config/bootstrap.php

use Nurtjahjo\StoremgmCA\Domain\Repository\ProductRepositoryInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;
use Nurtjahjo\StoremgmCA\Domain\Service\StorageServiceInterface;

use Nurtjahjo\StoremgmCA\Infrastructure\Database\ConnectionFactory;
use Nurtjahjo\StoremgmCA\Infrastructure\Logger\FileLogger;
use Nurtjahjo\StoremgmCA\Infrastructure\Repository\ProductPdoRepository;
use Nurtjahjo\StoremgmCA\Infrastructure\Service\LocalStorageService;

// Load configuration
$appConfig = require __DIR__ . '/app.php';
$dbConfig = require __DIR__ . '/database.php';
// $mailConfig = require __DIR__ . '/mail.php'; // Uncomment jika mailer sudah siap

try {
    // 1. Inisialisasi Database Connection
    $pdo = ConnectionFactory::create($dbConfig);
    $prefix = $dbConfig['connections']['usermgm-ca']['prefix'] ?? ''; // Menggunakan key koneksi default storemgm
    
    // Override prefix jika menggunakan key yang berbeda di database.php storemgm
    // Asumsi: config/database.php storemgm menggunakan key 'storemgm-ca' atau defaultnya
    if (isset($dbConfig['connections']['default']['prefix'])) {
        $prefix = $dbConfig['connections']['default']['prefix'];
    } elseif (isset($dbConfig['connections']['storemgm-ca']['prefix'])) {
        $prefix = $dbConfig['connections']['storemgm-ca']['prefix'];
    }

} catch (Throwable $e) {
    // Fail fast jika database mati
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database unavailable', 'detail' => $e->getMessage()]);
    exit;
}

// 2. Dependency Container Initialization
$container = [];

// 3. Bind Core Services
// ---------------------

// Logger
$container[LoggerInterface::class] = new FileLogger($appConfig['storemgm-ca']['log_path']);

// Storage Service (VITAL untuk storemgm)
// Kita menyuntikkan path dari config/app.php ke dalam constructor LocalStorageService
$container[StorageServiceInterface::class] = new LocalStorageService(
    $appConfig['storemgm-ca']['storage_path'],
    $container[LoggerInterface::class]
);

// 4. Bind Repositories
// --------------------

// Product Repository
// Kita menyuntikkan nama tabel yang sudah diberi prefix
$container[ProductRepositoryInterface::class] = new ProductPdoRepository(
    $pdo,
    $prefix . 'products',
    $prefix . 'categories'
);

// Tambahkan binding repository lain di sini nanti (Cart, Order, dll)
// $container[CartRepositoryInterface::class] = new CartPdoRepository($pdo, $prefix . 'carts');


// 5. Automatic Dependency Resolver (Reflection)
// ---------------------------------------------
// Fungsi ini memungkinkan kita membuat UseCase tanpa harus mendaftarkannya satu per satu
// selama dependensinya (Interface) sudah terdaftar di $container di atas.
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

return $container;
