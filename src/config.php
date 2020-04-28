<?php
return [
    'connection' => [
        'host' => '127.0.0.1',//主机
        'port' => 5672,//端口
        'user' => 'kol_server',//用户名
        'password' => 'kol_server123',//密码
        'vhost' => '/',//虚拟机
        'channel' => [
            'id' => '',//信道ID
            'exchange' => [
                'name' => 'KolServer_Client_MessageExchange',//交换机名称
                'type' => 'direct',//交换器类型
                'passive' => false,//是否被动
                'durable' => true,//是否持久化
                'auto_delete' => false//是否自动删除
            ],
            'routing_key' => 'KolServer_Client_MessageRoutingKey',//路由键
            'queue' => [
                'name' => 'KolServer_Client_MessageQueue',//队列名称
                'passive' => false,//是否被动
                'durable' => true,//是否持久化
                'exclusive' => false,//是否排他
                'auto_delete' => false//是否自动删除
            ]
        ]
    ]
];