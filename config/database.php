<?php
// file: config/database.php

return [
    // Koneksi default yang akan digunakan
    'default' => 'storemgm-ca',

    'connections' => [
        'storemgm-ca' => [
            // DSN (Data Source Name) untuk PDO
            // Pastikan nama database 'storemgm_db' sudah dibuat di MySQL Anda
            'dsn'      => 'mysql:host=127.0.0.1;dbname=nurtjahj_storemgm;charset=utf8mb4',
            
            // Kredensial Database (SESUAIKAN DENGAN LOKAL ANDA)
            'username' => 'root',
            'password' => '300966', 
            
            // Prefix Tabel
            // Ini PENTING karena di scheme.sql nama tabelnya adalah 'storemgm_products'
            // Bootstrap akan menggabungkan 'storemgm_' + 'products'
            'prefix'   => 'storemgm_',
        ],
    ],
];
