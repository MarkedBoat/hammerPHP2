<?php


    namespace cli\user;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;

    ini_set('memory_limit', '1024M');

    class CmdExport extends CmdBase {


        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }

        public function hasVipId() {
            die("任务结束，防止误操作");

            $phpFile = Sys::app()->params['cli']['logDir'] . '/vip_user.csv';
            $goon    = true;
            $i       = 0;
            $lastId  = 0;
            file_put_contents($phpFile, '');
            while ($goon) {
                $table = Sys::app()->db('cli_bftv_slave')->setText("SELECT id,vipouttime FROM std_user WHERE vipouttime>0 and id>'{$lastId}' ORDER BY id ASC LIMIT 1000")->queryAll();
                if (is_array($table)) {
                    if (count($table) < 1000)
                        $goon = false;
                    foreach ($table as $row) {
                        $str = "{$i},{$row['id']},{$row['vipouttime']}\n";
                        file_put_contents($phpFile, $str, FILE_APPEND);
                        $lastId = $row['id'];
                        $i++;
                    }
                    echo date('Y-m-d H:i:s', time()) . '#' . $str;
                } else {
                    $goon = false;
                }

            }
            echo date('Y-m-d H:i:s', time());
            echo "ok\n";
        }

        public function onlined_uuid() {
            if ($this->inputBox->tryGetString('danger') !== 'yes')
                die("任务结束，防止误操作");
            $file     = Sys::app()->params['cli']['logDir'] . '/vip_user.csv';
            $file2    = Sys::app()->params['cli']['logDir'] . '/allvip_uuid.csv';
            $f        = fopen($file, 'r');
            $fileLine = 0;
            $uidsVip  = [];
            $nowTs    = time();
            while (!feof($f)) {
                $fileLine++;
                $str = trim(fgets($f));
                echo $str . "\n";
                if (strlen($str) > 10) {
                    list($i, $uid, $expires) = explode(',', $str);
                    if (0 < intval($expires))
                        $uidsVip[] = $uid;
                }
            }
            fclose($f);
            $uuids = [];
            $cnt   = count($uidsVip);
            //substr($str,-5,1)
            $cmd = Sys::app()->db('cli_bftv_slave')->setText("SELECT uuid FROM std_user_logintoken WHERE uid=:uid ");
            $j   = 0;
            file_put_contents($file2, '');
            foreach ($uidsVip as $i => $uid) {
                $uuids2 = [];
                $table  = $cmd->bindArray([':uid' => $uid])->queryAll();
                foreach ($table as $row) {
                    if (substr($row['uuid'], -5, 1) !== '_')
                        continue;
                    if (!in_array($row['uuid'], $uuids2, true)) {
                        $uuids2[] = $row['uuid'];
                        if (!in_array($row['uuid'], $uuids, true)) {
                            $uuids[] = $row['uuid'];
                            $str     = "{$row['uuid']}\n";
                            echo "{$j}/{$i}/{$cnt} {$str}";
                            file_put_contents($file2, $str, FILE_APPEND);
                            $j++;
                        }
                    }
                }

            }


        }
    }