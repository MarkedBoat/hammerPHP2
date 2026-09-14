<?php


namespace cli\dev\code;

use hammer\cli\CmdBase;

ini_set('memory_limit', '2048M');


class CmdTmp extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    /**
     * 导出连过网的
     */
    public function echo_conf()
    {
        $conf_file = $this->inputBox->getNotEmptyString('conf_file');
        $key       = $this->inputBox->getNotEmptyString('key');
        include $conf_file;
        $json = json_encode([\Conf_Mysql::TRY_MAX, \Conf_Mysql::$dbconf]);
        echo "{$key}\n{$json}\n";
        file_put_contents('/var/log/porter/pmysql', "{$key}###{$json}\n", FILE_APPEND);
    }

    public function start_echo_conf()
    {
        $str  = '/data/code/poseidon_server/fcgapi.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/hm.hades.fun.tv/conf/mysql_conf.php
/data/code/poseidon_server/pam.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-sync/mysql_conf.php
/data/code/poseidon_server/papi.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pb.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pc.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pl.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/plets.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pm.funshion.com/conf-alpha/mysql_conf.php
/data/code/poseidon_server/pm.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/pmsg.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/po.funshion.com/conf-alpha/mysql_conf.php
/data/code/poseidon_server/po.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/ps.funshion.com/conf-alpha/mysql_conf.php
/data/code/poseidon_server/ps.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/ps.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/psi.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/psmart.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/psms.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/ptp.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pu.funshion.com/conf-alpha/mysql_conf.php
/data/code/poseidon_server/pu.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/pv.funshion.com/conf-alpha/mysql_conf.php
/data/code/poseidon_server/pv.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/pvip.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pwa.funshion.com/conf/mysql_conf.php
/data/code/poseidon_server/pa.funshion.com/conf-online/mysql_conf.php
/data/code/poseidon_server/pa.funshion.com/conf/mysql_conf.php';
        $strs = explode("\n", $str);
        file_put_contents('/var/log/porter/pmysql', '');
        $opens = [];
        foreach ($strs as $file)
        {
            echo "$file\n";
            $key     = str_replace(['/data/code/poseidon_server/', '/mysql_conf.php'], '', $file);
            $cmd     = "/hammer_porter dev/code/tmp echo_conf --env=dev0 --conf_file='{$file}' --key={$key}";
            $opens[] = popen($cmd, 'w');
            echo "\n{$cmd}\n";
        }
        foreach ($opens as $open)
        {
            pclose($open);
        }
        $str   = file_get_contents('/var/log/porter/pmysql');
        $strs  = explode("\n", $str);
        $confs = [];

        foreach ($strs as $str)
        {
            if (empty($str))
            {
                continue;
            }
            echo "<<<{$str}\n";
            list($key, $json) = explode('###', $str);
            $confs[$key] = json_decode($json, true);
            echo ">>>\n";
        }
        file_put_contents('/var/log/porter/pmysql.json', json_encode($confs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


    }

    private $_hosts    = [];
    private $_accounts = [];

    private function getHostIndexs($hosts)
    {
        $json = json_encode($hosts);
        if (!in_array($json, $this->_hosts))
        {
            $this->_hosts[] = $json;
        }
        return array_search($json, $this->_hosts);
    }

    private function getAccountIndex($account)
    {
        $json = json_encode($account);
        if (!in_array($json, $this->_accounts))
        {
            $this->_accounts[] = $json;
        }
        return array_search($json, $this->_accounts);
    }

    ///hammer_porter dev/code/tmp analyzed --env=dev0
    public function analyze()
    {
        $confs      = json_decode(file_get_contents('/var/log/porter/pmysql.json'), true);
        $tryMax     = [];
        $compare    = [];
        $to_conf    = [];
        $conf_count = count($confs);
        $i          = 0;
        $j          = 0;
        $tabStr     = '           ';
        //5个维度 project/key/hosts/account/dbname
        $projects    = [];
        $conf_keys   = [];
        $confs_re    = [];//重置的confs
        $conf_in_map = ['host' => [], 'conf_key' => [], 'project' => [], 'account' => [], 'dbname' => []];
        $unique_map  = [];
        foreach ($confs as $project => $data)
        {
            if (strstr($project, 'conf-alpha'))
            {
                continue;
            }
            $projects[]            = $project;
            $default_servers_index = false;
            foreach ($data[1] as $conf_key => $conf)
            {
                if ($conf_key === 'servers')
                {
                    $default_servers       = $conf;
                    $default_servers_index = $this->getHostIndexs($conf);
                    continue;
                }
                if ($conf_key === 'encode')
                {
                    continue;
                }
                if (!isset($conf['user']) || !isset($conf['passwd']) || !isset($conf['dbname']))
                {
                    echo "{$conf_keys}\n";
                    var_dump($conf);
                    echo "\n";
                    die;
                }
                $host_index = $default_servers_index;
                if (isset($conf['servers']))
                {
                    $host_index = $this->getHostIndexs($conf['servers']);
                }
                $conf_re_index = count($confs_re);
                $confs_re[]    = [
                    'project'  => $project,
                    'conf_key' => $conf_key,
                    'host'     => $host_index,
                    'account'  => $this->getAccountIndex([$conf['user'], $conf['passwd']]),
                    'dbname'   => $conf['dbname'],
                    'user'     => $conf['user']
                ];
                foreach ($confs_re[$conf_re_index] as $k => $v)
                {
                    if (!isset($conf_in_map[$k]))
                    {
                        $conf_in_map[$k][$v] = [];
                    }
                    $conf_in_map[$k][$v][] = $confs_re[$conf_re_index];
                }

                if (!isset($unique_map[$conf_key]))
                {
                    $unique_map[$conf_key] = array();
                }
                $host_key = "hosts_{$host_index}_";
                if (!isset($unique_map[$conf_key][$host_key]))
                {
                    $unique_map[$conf_key][$host_key] = array();
                }
                if (!isset($unique_map[$conf_key][$host_key][$conf['user']]))
                {
                    $unique_map[$conf_key][$host_key][$conf['user']] = array();
                }
                if (!isset($unique_map[$conf_key][$host_key][$conf['user']][$conf['dbname']]))
                {
                    $unique_map[$conf_key][$host_key][$conf['user']][$conf['dbname']] = array();
                }
                $unique_map[$conf_key][$host_key][$conf['user']][$conf['dbname']][] = $project;
            }
        }
        echo "\n-----------HOST --------\n";
        foreach ($this->_hosts as $host_index => $host_json)
        {
            echo "{$tabStr}{$host_index}:{$host_json}\n";
        }
        echo "\n-----------ACCOUNT --------\n";
        $accounts = [];
        foreach ($this->_accounts as $account_index => $account_json)
        {
            $ar                       = json_decode($account_json, true);
            $accounts[$account_index] = $ar[0];
            //echo "{$tabStr}{$account_index}:{$account_json}\n";
            //echo "'_{$ar[0]}_'=>array('{$ar[0]}','{$ar[1]}'),\n";
            echo "const account_{$ar[0]}= '{$ar[0]}{,}{$ar[1]}';\n";

        }
        echo "\n-----------PORJECT --------\n";
        $i = 0;
        foreach ($conf_in_map['project'] as $project => $array)
        {
            echo "{$tabStr}{$i}:{$project}\n";
            $i++;
        }
        echo "\n-----------CONF KEY --------\n";
        $i = 0;
        foreach ($conf_in_map['conf_key'] as $conf_key => $array)
        {
            //echo "{$tabStr}{$i}:{$conf_key}\n";
            echo "const conf_{$conf_key}= '{$conf_key}';\n";
            $i++;
        }
        echo "\n-----------DB NAME --------\n";
        $i = 0;
        foreach ($conf_in_map['dbname'] as $dbname => $array)
        {
            // echo "{$tabStr}{$i}:{$dbname}\n";
            $i++;
            echo "const dbname_{$dbname}= '{$dbname}';\n";
        }

        $cnt = count($conf_in_map['host']);
        echo "\n-----------HOST MAP {$cnt}--------\n";
        echo 'array(';
        foreach ($this->_hosts as $host_index => $host_json)
        {
            // echo ">{$host_index}:{$host_json}\n";
            $conf_keys_tmp = [];
            $accounts_tmp  = [];
            $dbnames_tmp   = [];
            foreach ($conf_in_map['host'][$host_index] as $j => $conf)
            {
                $conf_keys_tmp[] = $conf['project'] . '.' . $conf['conf_key'];
                $accounts_tmp[]  = $accounts[$conf['account']];
                $dbnames_tmp[]   = $conf['dbname'];
            }
            switch (2){
                case 1:
                    echo "'hosts_{$host_index}_'=>";
                    echo var_export([
                        'hosts'    => json_decode($host_json, true),
                        'accounts' => ',' . join(',', array_unique($accounts_tmp)) . ',',
                        'dbnames'  => ',' . join(',', array_unique($dbnames_tmp)) . ','
                    ]);
                    // echo ',//'.join(',',$conf_keys_tmp)."\n";
                    echo ",\n";
                    break;
                case 2:
                    echo "const hosts_{$host_index}_= 'hosts_{$host_index}_';\n";
                    break;
            }

        }
        echo ")\n";

        echo "\n-----------CONFS--------\n";
        //                 $unique_map[$conf_key][$host_key][$conf['user']][$conf['dbname']][] = $project;
        foreach ($unique_map as $conf_key => $row1)
        {
            //echo "//{$conf_key}\n";
            if (count($row1) === 1)
            {
                echo "{$tabStr}//ok hosts\n";
            }
            else
            {
                echo "{$tabStr}//多个hosts\n";
            }
            foreach ($row1 as $host_key => $row2)
            {
                if (count($row2) === 1)
                {
                   // echo "{$tabStr}{$tabStr}//ok user\n";
                }
                else
                {echo "{$tabStr}{$tabStr} $host_key//多个user\n";
                    var_dump($row2);
                    die;

                }
                foreach ($row2 as $user => $row3)
                {
                    if (count($row3) === 1)
                    {
                        //echo "{$tabStr}{$tabStr}{$tabStr}//ok dbname\n";
                    }
                    else
                    { // echo "{$tabStr}{$tabStr}{$tabStr} $user//多个dbname\n";
                       // var_dump($user,$row3);
                       // die;

                    }
                    foreach ($row3 as $dbname => $row4)
                    {

                        $projects_str = join(',',array_unique($row4));
                        echo "'{$conf_key}' => array(Common_Dbconf::{$host_key}, Common_Dbconf::account_{$user}, Common_Dbconf::dbname_{$dbname}),//{$projects_str}\n";
                    }
                }
            }
        }
        die;
        foreach ($confs as $key => $conf)
        {
            if (strstr($key, 'conf-alpha'))
            {
                continue;
            }
            $j++;
            $tryMax[] = $conf[0];
            if (!isset($conf[1]['encode']))
            {
                $i++;
                echo "{$i}:{$key}\n";
            }

            foreach ($conf[1] as $k => $array)
            {
                $json = json_encode($array);
                if (strstr($json, '192'))
                {
                    continue;
                }
                $str       = $k . '###' . $json;
                $compare[] = $str;
                if (!isset($to_conf[$str]))
                {
                    $to_conf[$str] = [];
                }
                $to_conf[$str][] = $key;
            }
        }
        /**
         * hosts 为核心
         * account，dbname 各自来一套，与hosts关联
         * 以上三者都在 project  索引下,方便归纳
         */
        echo "config file count:{$conf_count} => {$j} => {$i}\n";

        $tryMaxCount = array_count_values($tryMax);
        foreach ($tryMaxCount as $val => $cnt)
        {
            echo "{$val}:{$cnt}\n";
        }
        echo "\n-----:\n";
        $conf_count = array_count_values($compare);
        ksort($conf_count);
        $dbkey_map = [];
        $cnd_map   = [];

        foreach ($conf_count as $val => $cnt)
        {
            echo "{$cnt}:{$val}\n";
            list($dbkey, $cnd) = explode('###', $val);
            if (!isset($dbkey_map[$dbkey]))
            {
                $dbkey_map[$dbkey] = [];
            }
            if (!isset($cnd_map[$cnd]))
            {
                $cnd_map[$cnd] = [];
            }
            $dbkey_map[$dbkey][] = $cnd;
            $cnd_map[$cnd][]     = $dbkey;
        }
        echo "\n-----------dbkey index --------\n";
        ksort($dbkey_map);
        foreach ($dbkey_map as $dbkey => $cnds)
        {
            $cnds      = array_unique($cnds);
            $has_count = count($cnds);
            echo "{$tabStr}dbkey: =>{$has_count} {$dbkey}\n";
            foreach ($cnds as $cnd)
            {
                $conf_val_key = $dbkey . '###' . $cnd;
                if (isset($to_conf[$conf_val_key]))
                {
                    $cnt = count($to_conf[$conf_val_key]);
                    echo "{$tabStr}{$tabStr}{$cnt})$cnd";
                    echo "\n{$tabStr}{$tabStr}|{$tabStr}";
                    echo join("\n{$tabStr}{$tabStr}|{$tabStr}", $to_conf[$conf_val_key]);
                    echo "\n";
                }
                else
                {
                    echo "{$tabStr}|{$tabStr}|0)$cnd";
                }
            }
        }
        echo "\n";


        echo "\n-----------cnn index --------\n";
        ksort($cnd_map);
        foreach ($cnd_map as $cnd => $dbkeys)
        {
            $dbkeys    = array_unique($dbkeys);
            $has_count = count($dbkeys);
            echo "{$tabStr}cnn: =>{$has_count} {$cnd} \n";
            foreach ($dbkeys as $dbkey)
            {
                $conf_val_key = $dbkey . '###' . $cnd;
                if (isset($cnd_map[$cnd]))
                {
                    $cnt = count($to_conf[$conf_val_key]);
                    echo "{$tabStr}{$tabStr}{$cnt})$dbkey";
                    echo "\n{$tabStr}{$tabStr}|{$tabStr}";
                    echo join("\n{$tabStr}{$tabStr}|{$tabStr}", $to_conf[$conf_val_key]);
                    echo "\n";
                }
                else
                {
                    echo "{$tabStr}|{$tabStr}|0)$cnd";
                }
            }
        }
        echo "\n";


        echo "\n";
        // var_dump($to_conf);
        echo "\n";

        echo "\n";
        // var_dump($to_conf);
        echo "\n";

    }


}