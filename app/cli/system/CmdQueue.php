<?php

    /**
     * Created by PhpStorm.
     * User: markedboat
     * Date: 2018/7/20
     * Time: 11:01
     */

    namespace cli\system;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;

    //更新计划凭借代码方法 sh ~/kinglone.sh "/data/git-webroot/api-htdocs/CLI/" "origin/master"
    // 简易计划任务操作  sudo chmod +x /data/git-webroot/api-htdocs/CLI/itfc/app cmd:/data/git-webroot/api-htdocs/CLI/itfc/app bftv.user.service.renew starter --service=kids --planId=renewKidsMember --env=prod --deadLineTs=1550222461
    class CmdQueue extends CmdBase {


        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }


        public function call() {
            echo "\n fuck\n";
            $timeout   = $this->inputBox->getNotEmptyInt('timeLimit');
            $startTime = time();
            $endTime   = intval(($startTime + $timeout) / 60) * 60 - 20;//前面除乘60，是为了消除时间误差,后面-20s是为了提前结束,不在作业中被 timeout kill 掉
            $redis     = Sys::app()->redis();
            $d1        = date('Y-m-d H:i:s', $startTime);
            echo "start:{$d1}\n";
            $className = $this->inputBox->getNotEmptyString('class');
            $object    = new $className;
            $fun       = $this->inputBox->getNotEmptyString('method');
            $queueName = $this->inputBox->getNotEmptyString('queue');
            $tab       = str_repeat(' ', 10);
            while (TRUE) {
                echo "{$tab}";
                $str = $redis->rpop($queueName);
                //$str='{"userPk":11,"title":"ok","time":11111111}';
                if ($str) {
                    $redis->rPush($queueName, $str);
                    $ts = time();
                    if (($startTime + 110) < $ts) {//mysql设置的是120秒断掉
                        $d1 = date('Y-m-d H:i:s', $ts);
                        echo "\n< relink {$d1}";

                        $startTime = $ts;
                        echo ">\n";
                    }
                    echo "$str#{$d1}";
                    if ($this->isCmdShutdown() === true) {
                        echo "\n  收到shutdown 指令,退出 \n";
                        break;
                    }
                    //为了保证输出格式，被调用函数自带tab起点为  str_repeat 20 空格
                    $result = $object->$fun($str);
                    //'ok', 'ignore', 'error'
                    if (in_array($result, ['ok', 'ignore']))
                        $redis->rpop($queueName);
                    echo "\n";
                    if ($ts >= $endTime) {
                        $d1 = date('Y-m-d H:i:s', $ts);
                        $d2 = date('Y-m-d H:i:s', $endTime);
                        echo "\n  程序安全跳出 {$d1}>={$d2}\n";
                        break;
                    }
                } else {
                    echo '<sleep ';
                    sleep(5);
                    $ts = time();
                    $d1 = date('Y-m-d H:i:s', $ts);
                    echo "#$d1>\n";
                    if ($this->isCmdShutdown() === true) {
                        echo "\n  收到shutdown 指令,退出 \n";
                        break;
                    }
                    if ($ts >= $endTime) {
                        // $d1 = date('Y-m-d H:i:s', $ts);
                        $d2 = date('Y-m-d H:i:s', $endTime);
                        echo "\n  程序安全跳出 {$d1}>={$d2}\n";
                        break;
                    }
                }
                echo "\n";
            }
        }

        public function test() {
            ///sl_hammer system/queue call --env=test queue=t2  class=\\cli\\applet\\notify\\Payed --method=push --timeLimit=200
            Sys::app()->redis()->lPush('t2', json_encode(['userPk' => 11, 'title' => 'ok', 'time' => 11111111]));
        }
    }