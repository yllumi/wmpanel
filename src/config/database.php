<?php
return  [
    'default' => 'mysql',
    'connections' => [
        'default' => [
            'driver'      => 'mysql',
            'host'        => getenv('database.default.hostname') ?: 'localhost',
            'port'        => '3306',
            'database'    => getenv('database.default.database') ?: '',
            'username'    => getenv('database.default.username') ?: '',
            'password'    => getenv('database.default.password') ?: '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_general_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => null,
            'options'   => [
                PDO::ATTR_EMULATE_PREPARES => false, // Must be false for Swoole and Swow drivers.
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
        'pesantren' => [
            'driver'      => 'mysql',
            'host'        => getenv('database.pesantren.hostname') ?: 'localhost',
            'port'        => '3306',
            'database'    => getenv('database.pesantren.database') ?: '',
            'username'    => getenv('database.pesantren.username') ?: '',
            'password'    => getenv('database.pesantren.password') ?: '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_general_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => null,
            'options'   => [
                PDO::ATTR_EMULATE_PREPARES => false, // Must be false for Swoole and Swow drivers.
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];