<?php
defined('ENV_NAME') or define('ENV_NAME', 'dev_root');

$dev0      = [
    'connectionString' => 'mysql:host=localhost;port=3306;dbname=sys',
    'username'         => 'root',
    'password'         => 'kinglone',
    'charset'          => 'utf8',
    'readOnly'         => true,
    'attributes'       => [
        \PDO::ATTR_TIMEOUT => 1
    ],
    'slaveConfig'      => [
        'connectionString' => 'mysql:host=localhost;port=3306;dbname=sys',
        'username'         => 'root',
        'password'         => 'kinglone',
        'charset'          => 'utf8',
        'attributes'       => [
            PDO::ATTR_TIMEOUT => 10,
        ],
    ]
];

return [
    'db'        => [
        'dev0'      => $dev0,
    ],
    'redis'     => [
        'default' => ['host' => '127.0.0.1', 'port' => 6379, 'password' => '', 'db' => 0],
        'sl2'     => ['host' => 'redis-node02', 'port' => 6389, 'password' => '', 'db' => 10],
    ],
    'memcached' => [
        ['memcache.server1', 11211],
        ['memcache.server2', 11211],
        ['memcache.server3', 11211],
        ['memcache.server4', 11211],
    ],
    'params'    => [
        'debugSign'     => 'debug',
        'errorHttpCode' => 200,
        'cli'       => [
            'phpFile'        => '/usr/bin/php',
            'hammerDir'      => '/data/code/porter/app',
            'logDir'         => '/var/log/porter',
            'root_cmd_queue' => 'root_cmd_queue',
            'tasks'          => [
                'root_cmd' => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'system/queue call --env=dev0 queue=root_cmd_queue class=\\\console\\\dev\\\cmd\\\CmdRoot --method=cmd_web_input --timeLimit=200',
                    'comment'   => '处理root cmd 队列',
                    'status'    => true,
                    'maxLimit'  => 1,
                    'timeLimit' => 7100,
                    'logstyle'  => ['Ymd', '', '>>'],
                ],
            ]
        ],

    ],
];
