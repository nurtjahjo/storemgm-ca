<?php
// file: config/app.php

return [
    'storemgm-ca' => [
        'base_url' => 'http://storemgm-ca.local',
        'log_path' => __DIR__ . '/../storage/logs/storemgm',
        'storage_path' => __DIR__ . '/../storage/app',
        'key' => 'e8b222332dccbad313b003d6c476757ba8d6009718f5648c65fc4344b49369d9',

        // KUNCI KEAMANAN PAYMENT GATEWAY (PENTING UNTUK WEBHOOK)
        // Di production, ini didapat dari dashboard Midtrans
        'payment_server_key' => 'SB-Mid-server-GwUP_mock_key_12345',

        'url_templates' => [
            'order_detail' => 'http://main.swaraksara.id/account/orders/{id}',
        ],
        'email_templates_path' => realpath(__DIR__ . '/../resources/templates/emails'),
    ],
    'app' => [
        'name' => 'Swaraksara Store',
    ],
];
