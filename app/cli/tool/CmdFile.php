<?php


namespace cli\tool;

use hammer\cli\CmdBase;

ini_set('memory_limit', '3072M');


class CmdFile extends CmdBase
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
     * 对比导出数据
     */
    public function diff()
    {
        echo "\n****************************************************\n file_more -  file_less =result\n****************************************************\n";
        $fmore = $this->inputBox->getNotEmptyString('file_more');
        $fless = $this->inputBox->getNotEmptyString('file_less');
        $fdiff = $this->inputBox->getNotEmptyString('outfile');
        echo "\n{$fmore}  {$fless}  {$fdiff}\n****************************************************\n";

        $ar_more  = explode("\n", file_get_contents($fmore));
        $ar_less  = explode("\n", file_get_contents($fless));
        $ar_diff  = array_diff($ar_more, $ar_less);
        $cnt_more = count($ar_more);
        $cnt_less = count($ar_less);
        $cnt_diff = count($ar_diff);
        echo "more:{$cnt_more}  less:{$cnt_less} diff:{$cnt_diff}\n";

        $r = file_put_contents($fdiff, join("\n", $ar_diff));
        var_dump($r);
        echo "\n";
    }

    public function getArrayKeys()
    {
        $str   = '/data/code/poseidon_server/fcgapi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/hm.hades.fun.tv/conf/redis_conf.php
/data/code/poseidon_server/pa.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pa.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pam.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/papi.funshion.com/conf-sync/redis_conf.php
/data/code/poseidon_server/pb.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pcap.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pc.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/plets.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pl.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pm.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pm.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pmsg.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/po.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/po.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/ps.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/ps.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/psi.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/psmart.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/psmart.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/psms.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/ptp.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pu.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pv.funshion.com/conf-online/redis_conf.php
/data/code/poseidon_server/pv.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pvip.funshion.com/conf/redis_conf.php
/data/code/poseidon_server/pwa.funshion.com/conf/redis_conf.php';
        $files = explode("\n", $str);
        $keys  = array();
        $p     = '/\'\w+\'/is';
        foreach ($files as $file)
        {
            $str = file_get_contents($file);
            $r   = preg_match_all($p, $str, $ar);
            if ($r){
                //var_dump($ar);
                $keys = array_merge($keys, $ar[0]);
            }
        }
        $keys=array_unique($keys);
        var_dump($keys);
        $str=join(',',$keys);
        $str=str_replace("'",'',$str);
        echo "\n{$str}\n";
    }
}