<?php
defined('ENV_NAME') or define('ENV_NAME', 'dev0');

$dbs = array();


$db_slave      = [
    'connectionString' => 'mysql:host=192.168.8.194;port={port};dbname={dbname}',
    'username'         => 'poseidon_test',
    'password'         => 'funshion_test',
    'charset'          => 'utf8',
    'readOnly'         => true,
    'attributes'       => [
        \PDO::ATTR_TIMEOUT => 1
    ]
];

$dbinfos_slave = array(
    array(
        'port' => '3307',
        'dbs'  => 'information_schema,db_poseidon_aggregate_media,db_poseidon_article,db_poseidon_btsync,db_poseidon_btsync_stats,db_poseidon_config,db_poseidon_credit,db_poseidon_media,db_poseidon_msg,db_poseidon_operation,db_poseidon_operation_b,db_poseidon_plets,db_poseidon_policy,db_poseidon_push,db_poseidon_search,db_poseidon_site,db_poseidon_site1,db_poseidon_sms,db_poseidon_statistics,db_poseidon_warning,db_thirdpartnar,kuaikan_video,mysql,performance_schema,poseidon_authority,poseidon_home,poseidon_media,poseidon_policy,poseidon_site,poseidon_user,poseidon_video,test,video'
    ),
    array(
        'port' => '3315',
        'dbs'  => 'information_schema,db_fcg_repository,db_poseidon_audit,mysql,performance_schema,test'
    ),
);



foreach ($dbinfos_slave as $dbinfo)
{
    $dbtmp                     = $db_slave;
    $dbtmp['connectionString'] = str_replace('{port}', $dbinfo['port'], $dbtmp['connectionString']);
    $dbnames                   = explode(',', $dbinfo['dbs']);
    foreach ($dbnames as $dbname)
    {
        $dbtmpc                     = $dbtmp;
        $dbtmpc['connectionString'] = str_replace('{dbname}', $dbname, $dbtmpc['connectionString']);
        $dbs[$dbname]               = $dbtmpc;
    }
}

$db_test      = [
    'connectionString' => 'mysql:host=192.168.16.131;port={port};dbname={dbname}',
    'username'         => 'root',
    'password'         => '123456',
    'charset'          => 'utf8',
    'readOnly'         => true,
    'attributes'       => [
        \PDO::ATTR_TIMEOUT => 1
    ]
];
$dbinfos_test = array(
    array(
        'port' => '3307',
        'dbs'  => 'information_schema,db_poseidon_aggregate_media,db_poseidon_article,db_poseidon_btsync,db_poseidon_btsync_stats,db_poseidon_config,db_poseidon_credit,db_poseidon_media,db_poseidon_msg,db_poseidon_operation,db_poseidon_operation_b,db_poseidon_plets,db_poseidon_policy,db_poseidon_push,db_poseidon_search,db_poseidon_site,db_poseidon_site1,db_poseidon_sms,db_poseidon_statistics,db_poseidon_warning,db_thirdpartnar,kuaikan_video,mysql,performance_schema,poseidon_authority,poseidon_home,poseidon_media,poseidon_policy,poseidon_site,poseidon_user,poseidon_video,test,video,information_schema,db_fcg_repository,db_poseidon_audit,mysql,performance_schema,test'
    )
);


foreach ($dbinfos_test as $dbinfo)
{
    $dbtmp                     = $db_test;
    $dbtmp['connectionString'] = str_replace('{port}', $dbinfo['port'], $dbtmp['connectionString']);
    $dbnames                   = explode(',', $dbinfo['dbs']);
    foreach ($dbnames as $dbname)
    {
        $dbtmpc                     = $dbtmp;
        $dbtmpc['connectionString'] = str_replace('{dbname}', $dbname, $dbtmpc['connectionString']);
        $dbs['T_' . $dbname]        = $dbtmpc;
    }
}



$dbs['cli']    = [
    'connectionString' => 'mysql:host=localhost;port=3306;dbname=dev_tool',
    'username'         => 'root',
    'password'         => 'kinglone',
    'charset'          => 'utf8',
    'readOnly'         => true,
    'attributes'       => [
        \PDO::ATTR_TIMEOUT => 1
    ]
];

return [
    'db'        => $dbs,
    'redis'     => [
        'sl2' => ['host' => 'redis-node02', 'port' => 6389, 'password' => '', 'db' => 10],
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
            'phpFile'   => '/usr/bin/php',
            'hammerDir' => '/data/code/porter/app',
            'logDir'    => '/var/log/porter',
            'tasks'     => [

                'appletNotifyPayed'        => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'system/queue call  queue=applet_notify_payed  class=\\\console\\\applet\\\notify\\\Payed --method=push',
                    'comment'   => '微信推送 订单支付',
                    'status'    => false,
                    'maxLimit'  => 1,
                    'timeLimit' => 7100,
                    'logstyle'  => ['Ymd', '', '>>'],
                ],
                'appletNotifyClientOnline' => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'system/queue call  queue=applet_notify_client_online  class=\\\console\\\applet\\\notify\\\ClientOnline --method=push',
                    'comment'   => '设备上线通知',
                    'status'    => false,
                    'maxLimit'  => 1,
                    'timeLimit' => 7100,
                    'logstyle'  => ['Ymd', '', '>>'],
                ],
                'slave_to_funtv'           => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'mysql/tmp slave_to_funtv',
                    'comment'   => '复制数据 bftv slave => funtv dev',
                    'status'    => false,
                    'maxLimit'  => 1,
                    'timeLimit' => 7200,
                    'logstyle'  => ['Y', 'md', '>>'],
                ],
                'git_deploy'               => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'system/project scan',
                    'comment'   => '代码部署',
                    'status'    => true,
                    'maxLimit'  => 1,
                    'timeLimit' => 7200,
                    'logstyle'  => ['Y', 'md', '>>'],
                ],
                'kill_plan_by_id'          => [
                    'time'      => ['*/1 */1 */1'],
                    'cmd'       => 'system/launcher killPlan',
                    'comment'   => '临时杀死任务',
                    'status'    => true,
                    'maxLimit'  => 1,
                    'timeLimit' => 7200,
                    'logstyle'  => ['Y', 'md', '>>'],
                ],
            ]
        ],

    ],
];
