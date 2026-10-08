<?php

include '_env.danger.php';

defined('ENV_NAME') or define('ENV_NAME', 'kl-pc');


return [
    'db'     => [
        'kl'     => [
            //            'connectionString' => 'mysql:host=172.17.0.1;port=3306;dbname=kl',
            'connectionString' => 'mysql:host=kl-laptop-2021;port=3306;dbname=kl',
            'username'         => 'root',
            'password'         => 'Xyz@2025',
            'charset'          => 'utf8mb4',
            'readOnly'         => true,
            'attributes'       => [//  \PDO::ATTR_TIMEOUT => 1
            ]
        ],
        'spider' => [
            //            'connectionString' => 'mysql:host=172.17.0.1;port=3306;dbname=kl',
            'connectionString' => 'mysql:host=kl-laptop-2021;port=3306;dbname=spider',
            'username'         => 'root',
            'password'         => 'Xyz@2025',
            'charset'          => 'utf8mb4',
            'readOnly'         => true,
            'attributes'       => [//  \PDO::ATTR_TIMEOUT => 1
            ]
        ],
    ],
    'redis'  => [

    ],
    'log'    => [
        //必须遵循 \hammer\logs\LogInterface
        'classname'     => '\hammer\logs\Log',
        //绝对路径
        'fullDir'       => '',
        //相对路径
        'dir'           => '/runtimes/log',
        //日志路径风格
        'datePathStyle' => 'Ymd',
        //php/json  php打印风格 和  json风格
        'dataStyle'     => 'json',
        //是否美化，主要针对json
        'isPretty'      => false,
    ],
    'params' => [
        'debugSign'     => 'debug',
        'errorHttpCode' => 200,
        'cli'           => [
            'phpFile'        => '/usr/bin/php',
            'hammerDir'      => '/mnt/f/doc/bfcode/porter/app',
            'logDir'         => '/mnt/f/doc/bfcode/porter/_file/env/docker/logs',
            'webFileDir'     => '/data/upload/cli_out',
            'root_cmd_queue' => 'root_cmd_queue',
            'tasks'          => [

            ]
        ],
        'http'          => [
            'resFormatterClassname' => 'models\common\api\ResFormatter',
        ],

    ],
];