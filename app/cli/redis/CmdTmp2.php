<?php


namespace cli\redis;

use hammer\cli\CmdBase;


class CmdTmp2 extends CmdBase
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
        $json = json_encode(\Conf_Redis::$conf);
        echo "{$key}\n{$json}\n";
        file_put_contents('/var/log/porter/predis', "{$key}###{$json}\n", FILE_APPEND);
    }

    public function ll()
    {
        $files = explode("\n", '/data/code/poseidon_server/pvip.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/fcgapi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/hm.hades.fun.tv/conf/redis_conf.php
/data/code/poseidon_server/pa.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pa.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pam.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-sync/redis_conf.php
/data/code/poseidon_server/pb.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pc.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pcap.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pl.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/plets.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pm.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pm.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pmsg.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pmsg.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/po.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/po.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/ps.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/ps.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/psi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/psmart.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/psmart.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/psms.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/ptp.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pu.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pu.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pv.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pv.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pwa.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pfmg.funshion.com/conf/redis_conf.php');

        $strs = $files;
        file_put_contents('/var/log/porter/predis', '');
        $opens = [];
        foreach ($strs as $file)
        {
            echo "$file\n";
            $key     = str_replace(['/data/code/poseidon_server/', '/redis_conf.php'], '', $file);
            $cmd     = "/hammer_porter redis/tmp2 echo_conf --env=dev0 --conf_file='{$file}' --key={$key}";
            $opens[] = popen($cmd, 'w');
            echo "\n{$cmd}\n";
        }
        foreach ($opens as $open)
        {
            pclose($open);
        }
        $str   = file_get_contents('/var/log/porter/predis');
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
        file_put_contents('/var/log/porter/predis.json', json_encode($confs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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


    ///hammer_porter dev/code/tmp analyzed --env=dev0
    public function analyze()
    {
        $confs      = json_decode(file_get_contents('/var/log/porter/predis.json'), true);
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
        $unique_map  = [];
        $hosts       = [];
        $hosts_jsons = [];
        $map         = [];
        $map2        = [];
        foreach ($confs as $project_name => $project_conf)
        {
            $tmp_json = json_encode($project_conf);

            if (strstr($project_name, 'conf-alpha') || strstr($tmp_json, '127.0.0.1') || strstr($tmp_json, '192.168'))
            {
                continue;
            }

            // var_dump($project_conf);
            $projects[]            = $project_name;
            $default_servers_index = false;
            foreach ($project_conf as $conf_key => $conf_hosts)
            {

                $host_master    = array_shift($conf_hosts);
                $tmp_host_jsons = [];
                foreach ($conf_hosts as $conf_host)
                {
                    $tmp_host_jsons[] = json_encode($conf_host);
                }
                $tmp_host_jsons = array_unique($tmp_host_jsons);
                sort($tmp_host_jsons);
                $host_jsons = [$host_master];
                foreach ($tmp_host_jsons as $json)
                {
                    $host_jsons[] = json_decode($json, true);
                }
                $str         = json_encode($host_jsons);
                $hosts[$str] = $host_jsons;
                if (!isset($map[$conf_key]))
                {
                    $map[$conf_key] = array();
                }
                if (!isset($map[$conf_key][$str]))
                {
                    $map[$conf_key][$str] = array();
                }
                $map[$conf_key][$str][] = $project_name;

                if (!isset($map2[$str]))
                {
                    $map2[$str] = array();
                }
                if (!isset($map2[$str][$conf_key]))
                {
                    $map2[$str][$conf_key] = array();
                }
                $map2[$str][$conf_key][] = $project_name;
            }


        }
        var_dump($map);
        var_dump($map2);
        echo "\n";
        echo json_encode($map2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n";
        var_export($map2);
        echo "\n";
        foreach ($map2 as $host_json=>$info)
        {
            var_export(json_decode($host_json,true));
            echo "\n";
        }
        echo "\n";
    }
}