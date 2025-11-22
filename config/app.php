<?php
// file: config/app.php

return [
    'storemgm-ca' => [
        'base_url' => 'http://storemgm-ca.local',
        
        // Path untuk Log
        'log_path' => __DIR__ . '/../storage/logs/storemgm',
        
        // Path Root untuk Penyimpanan File (Protected Catalog & Private Content)
        // Ini mengarah ke folder 'storage/app' di root proyek
        'storage_path' => __DIR__ . '/../storage/app',

        // Kunci enkripsi (untuk token atau kebutuhan lain)
        'key' => 'e8b222332dccbad313b003d6c476757ba8d6009718f5648c65fc4344b49369d9',

        'url_templates' => [
            // Template URL untuk dikirim ke email (jika perlu)
            'order_detail' => 'http://main.swaraksara.id/account/orders/{id}',
        ],

        'email_templates_path' => realpath(__DIR__ . '/../resources/templates/emails'),
    ],
    
    'app' => [
        'name' => 'Swaraksara Store',
    ],
];
