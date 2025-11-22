<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Database;

use PDO;
use PDOException;
use InvalidArgumentException;

class ConnectionFactory
{
    public static function create(array $config): PDO
    {
        // 1. Tentukan koneksi mana yang dipakai
        // Coba cari konfigurasi khusus storemgm dulu
        if (isset($config['connections']['storemgm-ca'])) {
            $connectionDetails = $config['connections']['storemgm-ca'];
        } 
        // Fallback ke default
        else {
            $defaultName = $config['default'] ?? null;
            if (!$defaultName || !isset($config['connections'][$defaultName])) {
                throw new InvalidArgumentException("Konfigurasi database tidak valid atau tidak ditemukan.");
            }
            $connectionDetails = $config['connections'][$defaultName];
        }

        // 2. Validasi
        if (!isset($connectionDetails['dsn'])) {
            throw new InvalidArgumentException("DSN database tidak ditemukan.");
        }

        // 3. Opsi PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // 4. Buat Koneksi
        return new PDO(
            $connectionDetails['dsn'],
            $connectionDetails['username'] ?? null,
            $connectionDetails['password'] ?? null,
            $options
        );
    }
}
