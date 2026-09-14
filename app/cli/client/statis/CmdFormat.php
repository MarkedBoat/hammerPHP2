<?php


    namespace cli\client\statis;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;


    class CmdFormat extends CmdBase {


        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }

        public function his() {
            $table   = Sys::app()->db('sl_slave')->setText('SELECT id FROM sl_client WHERE oper>0')->queryAll();
            $cidInDb = [];
            foreach ($table as $row)
                $cidInDb[] = $row['id'];
            $nTs   = time();
            $ndate = intval(date('Ymd', $nTs));
            $days  = [];
            for ($i = 2; $i < 50; $i++) {
                $days[] = intval(date('Ymd', ($nTs - 86400 * $i)));
            }
            //  $days[] = $ndate;
            //  rsort($days);
            $i      = 0;
            $n      = 0;
            $redis2 = Sys::app()->redis('sl');
            $line = str_repeat('0', 1440);
            $stas = [
                'null'         => 0,
                'lock'         => 1,
                'unlock'       => 2,
                'sleep'        => 3,
                'free.lock'    => 4,
                'free.unlock'  => 5,
                'grand.lock'   => 6,
                'grand.unlock' => 7,
            ];

            $db   = Sys::app()->db('sl_master')->setText('INSERT IGNORE INTO sl_client_statis SET cid=:cid,ymd=:ymd,str=:str,cdate=:cdate');
            $bind = [':cdate' => date('Y-m-d H:i:s', $nTs)];
            foreach ($days as $day) {
                $keyDay = 'sl_s_d_' . $day;//screenLock.statis.day
                $cids   = $redis2->sMembers($keyDay);
                $cnt    = count($cids);
                $n      += $cnt;
                echo "{$day}:$cnt\n";
                foreach ($cids as $j => $cid) {
                    $sta       = in_array($cid, $cidInDb) ? 'yes' : 'no';
                    $keyClient = 'sl_s_d_' . $day . '_' . $cid;
                    echo "{$day}:{$j}/{$cnt}  {$keyDay}  {$keyClient} {$sta}\t";
                    $redis2->sAdd($keyDay, $cid);
                    $points = $redis2->hGetAll($keyClient);
                    if ($sta === 'yes') {
                        if (count($points)) {
                            echo count($points);
                            $lineTmp = $line;
                            foreach ($points as $t => $status) {
                                $lineTmp[$t] = $stas[$status];
                            }
                            $bind[':cid'] = $cid;
                            $bind[':ymd'] = $day;
                            $bind[':str'] = $lineTmp;
                            $db->bindArray($bind)->execute();
                            //echo "\n$lineTmp";
                        }

                    }
                    $redis2->del($keyClient);
                    $i++;
                    echo "\n";
                }
                $redis2->del($keyDay);
            }
            echo "{$i}/{$n}\n";
        }


    }