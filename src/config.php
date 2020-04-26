<?php
return [
    'connection' => [
        'host' => '127.0.0.1',
        'port' => 5672,
        'user' => 'kol_server',
        'password' => 'kol_server123',
        'vhost' => '/',
        'channel' => [
            'id' => '',
            'exchange' => [
                'name' => 'KolServer_Client_MessageExchange',
                'type' => 'direct',
                'passive' => false,
                'durable' => false,
                'auto_delete' => false
            ],
            'routing_key' => 'KolServer_Client_MessageRoutingKey',
            'queue' => [
                'name' => 'KolServer_Client_MessageQueue'
            ]
        ]
    ]
];