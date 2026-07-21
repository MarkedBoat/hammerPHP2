<?php

namespace modules\dev\v1\api\funtv\conf\db;

use hammer\web\ActionBase;


class ActionMap00 extends ActionBase
{
    const TRY_MAX       = 3;
    const host0         = 'mysql:host=192.168.8.194;port=3307';
    const host1         = 'mysql:host=192.168.8.194;port=3307';
    const host_slave2_0 = 'mysql:host=192.168.8.194;port=3315';
    const host_slave2_1 = 'mysql:host=192.168.8.194;port=3315';
    const user          = 'poseidon_test';
    const passwd        = 'funshion_test';

//后期静态绑定代替了

    public function run()
    {

        $dbconf       = array(
            'servers'                    => array(
                self::host0,
                self::host1,
            ),
            'encode'                     => 'utf8',
            'poseidon_media'             => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_media'
            ),
            'poseidon_video'             => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'poseidon_video'
            ),
            'poseidon_user'              => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'poseidon_user'
            ),
            'poseidon_home'              => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'poseidon_home'
            ),
            'db_poseidon_cdn'            => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_cdn'
            ),
            'poseidon_aggregate'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_aggregate_media'
            ),
            'poseidon_media_old'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'poseidon_media'
            ),
            'poseidon_operation'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_operation'
            ),
            'poseidon_site'              => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_site'
            ),
            'db_poseidon_policy'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_policy'
            ),
            'db_poseidon_config'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_config'
            ),
            'poseidon_btsync'            => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_btsync'
            ),
            'db_poseidon_networkmonitor' => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_networkmonitor'
            ),
            'db_poseidon_user'           => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_user'
            ),
            'db_poseidon_interaction'    => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_interaction'
            ),
            'poseidon_vip'               => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_vip'
            ),
            'db_fcg_repository'          => array(
                'user'    => self::user,
                'passwd'  => self::passwd,
                'dbname'  => 'db_fcg_repository',
                'servers' => array(self::host_slave2_0, self::host_slave2_1)
            ),
            'db_fcg_repository_play'     => array(
                'user'    => self::user,
                'passwd'  => self::passwd,
                'dbname'  => 'db_fcg_repository',
                'servers' => array(self::host_slave2_0, self::host_slave2_1)
            ),
            'db_poseidon_audit'          => array(
                'user'    => self::user,
                'passwd'  => self::passwd,
                'dbname'  => 'db_poseidon_audit',
                'servers' => array(self::host_slave2_0, self::host_slave2_1)
            ),
            'poseidon_credit'            => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_credit'
            ),
            'db_poseidon_search'         => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_search'
            ),
            'db_poseidon_operation'      => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'db_poseidon_operation'
            ),
            'vdw'                        => array(
                'user'   => self::user,
                'passwd' => self::passwd,
                'dbname' => 'vdw'
            ),
        );
        $str          = 'poseidon_media,poseidon_video,poseidon_user,poseidon_home,db_poseidon_cdn,poseidon_aggregate,poseidon_media_old,poseidon_operation,poseidon_site,db_poseidon_policy,db_poseidon_config,poseidon_btsync,db_poseidon_networkmonitor,db_poseidon_user,db_poseidon_interaction,poseidon_vip,db_fcg_repository,db_fcg_repository_play,db_poseidon_audit,poseidon_credit,db_poseidon_search,db_poseidon_operation,vdw';
        $host131      = 'mysql:host=192.168.16.131;port=3307';
        $host194_more = 'mysql:host=192.168.8.194;port=3307';
        $host194_less = 'mysql:host=192.168.8.194;port=3315';
        $user194      = 'poseidon_test';
        $password194  = 'funshion_test';
        $user131      = 'root';
        $password131  = '12345';

        $confall = array();
        $skip    = array('servers', 'encode');
        $envs    = array(
            'slave' => array('user' => $user194, 'passwd' => $password194, 'hosts' => array($host194_more, $host194_more)),
            'test'  => array('user' => $user131, 'passwd' => $password131, 'hosts' => array($host131, $host131)),
            //'dev'   => array('user' => $user131, 'passwd' => $password131, 'hosts' => array($host131, $host131))
        );
        //return join(',', array_keys($dbconf));
        ksort($dbconf);
        foreach ($dbconf as $key => $conf)
        {
            if (in_array($key, $skip))
                continue;
            $db            = $conf['dbname'];
            $confall[$key] = array();
            foreach ($envs as $env => $env_conf)
            {
                $confall[$key][$env]           = $env_conf;
                $confall[$key][$env]['dbname'] = $db;
            }
        }
        $less_keys = array('db_fcg_repository', 'db_fcg_repository_play', 'db_poseidon_audit');
        foreach ($less_keys as $conf_key)
        {
            $confall[$conf_key]['slave']['hosts'] = array($host194_less, $host194_less);
        }

        return $confall;
    }

}