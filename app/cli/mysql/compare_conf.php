<?php
//引用总配置类
include('/data/code/poseidon_server/phplib/common/dbconf.php');
//将 Conf_Mysql::$dbconf 替换下 $dbconf
$dbconf = array(
    'servers'                 => array(
        0 => 'mysql:host=10.1.6.1;port=3306',
        1 => 'mysql:host=10.1.6.2;port=3306',
        2 => 'mysql:host=10.1.6.3;port=3306',
        3 => 'mysql:host=10.1.6.4;port=3306',
    ),
    'encode'                  => 'utf8',
    'poseidon_media'          => array(
        'user'   => 'poseidon_web',
        'passwd' => 'lfAPVoIsU+YI6U',
        'dbname' => 'db_poseidon_media'
    ),
    'poseidon_media_old'      => array(
        'user'   => 'poseidon_web',
        'passwd' => 'lfAPVoIsU+YI6U',
        'dbname' => 'poseidon_media'
    ),
    'db_poseidon_interaction' => array(
        'user'    => 'poseidon_inter',
        'passwd'  => 'kY8t2n#',
        'dbname'  => 'db_poseidon_interaction',
        'servers' => array(
            0 => 'mysql:host=10.1.6.59;port=3306',
            1 => 'mysql:host=10.1.6.21;port=3306',
        )
    ),
);

$confs           = array();
$default_servers = $dbconf['servers'];
unset($dbconf['servers']);
unset($dbconf['encode']);
foreach ($dbconf as $key => $row)
{
    Common_Dbconf::initConf($key);
    $json  = json_encode(array(
        'servers' => isset($row['servers']) ? $row['servers'] : $default_servers,
        'user'    => $row['user'],
        'passwd'  => $row['passwd'],
        'dbname'  => $row['dbname'],
    ));
    $json2 = json_encode(Common_Dbconf::$dbconf[$key]);
    //如果配置不一样，会输出
    if ($json !== $json2)
    {
        echo "\n-------------{$key}--------------\n{$json}\n{$json2}\n";
    }
}