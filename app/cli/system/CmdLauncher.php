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
    class CmdLauncher extends CmdBase {
        public static $logDir     = '';
        public static $shFileName = '';
        public static $hammerDir  = '';
        public static $env        = '';

        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }


        public function call() {
            echo "\n fuck\n";

            $phpFile   = Sys::app()->params['cli']['phpFile'];
            $hammerDir = Sys::app()->params['cli']['hammerDir'];;
            $env             = $this->inputBox->getNotEmptyString('env');
            $shFile          = self::$shFileName = $hammerDir . '/app.php';
            $logDir          = self::$logDir = Sys::app()->params['cli']['logDir'] . '/';
            self::$hammerDir = $hammerDir;
            self::$env       = $env;
            $nowTs           = time();
            if (!is_dir($logDir))
                exec("mkdir -p $logDir");

            $date = date('Y-m-d H:i:s', $nowTs);
            // echo "\n$date<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n";
            echo "\n\n\n<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<\n$date\n";
            $files = [];
            list($day, $hour, $min) = array_map(function ($ele) {
                return intval($ele);
            }, explode('#', date('d#H#i', $nowTs)));
            $tasks = Sys::app()->params['cli']['tasks'];
            foreach ($tasks as $planId => $plan) {
                echo "\n[[[[[[[[[[[[[[[[[[[[//{$date}//{$plan['comment']}\n$planId\nSTATUS:";
                if ($plan['status'] === true) {
                    echo "TRUE\nMAX_LIMIT:";
                    if ($plan['maxLimit'] > 0) {
                        $results = static::getPlanRunning($planId);
                        $cnt     = count($results);
                        echo " running:{$cnt}  limit:{$plan['maxLimit']} ";
                        if (count($results) >= $plan['maxLimit']) {
                            echo "OVER\n]]]]]]]]]]]]]]]]]]]]\n";
                            self::killTimeout($results, $nowTs);
                            continue;
                        } else {
                            echo "OK";
                        }
                        if (count($results)) {
                            self::killTimeout($results, $nowTs);
                        }
                    } else {
                        echo "NO LIMIT";
                    }
                    echo "\nTIME RANGE:";
                    $times = is_array($plan['time']) ? $plan['time'] : [$plan['time']];
                    foreach ($times as $crontabtime) {
                        list($min_, $hour_, $day_) = explode(' ', $crontabtime);
                        if (static::compareDate($min, $min_) && static::compareDate($hour, $hour_) && static::compareDate($day, $day_)) {
                            echo "IN\n";
                            $deadLineTs = ($nowTs + intval($plan['timeLimit'])) / 60 * 60;
                            $logDir     = self::$logDir . $planId;
                            $fn         = 'log';
                            $logType    = '>';
                            //logstyle 0:目录  1:文件名  2:文件内容追加类型
                            if (isset($plan['logstyle']) && is_array($plan['logstyle']) && count($plan['logstyle']) >= 2) {
                                $time = time();
                                if ($plan['logstyle'][0])
                                    $logDir = "{$logDir}/" . date($plan['logstyle'][0], $time);
                                if ($plan['logstyle'][1])
                                    $fn = 'log' . date($plan['logstyle'][1], $time);
                                if (isset($plan['logstyle'][2]) && in_array($plan['logstyle'][2], ['>', '>>'], true))
                                    $logType = $plan['logstyle'][2];
                            }

                            if (!is_dir($logDir))
                                exec("mkdir -m 777 -p $logDir");
                            $logFileName = "{$logDir}/{$fn}";
                            if (!file_exists($logFileName)) {
                                exec("touch {$logFileName}");
                                exec("chmod 777 {$logFileName}");
                            };

                            //$cmd = "$phpFile $shFile {$plan['cmd']}  --planId={$planId} --env={$env} --timeLimit={$plan['timeLimit']} --deadLineTs={$deadLineTs} --shFile={$shFile}  --logFile={$logFileName}";
                            $cmd = "$phpFile $shFile {$plan['cmd']}  --planId={$planId} --env={$env} --timeLimit={$plan['timeLimit']} --deadLineTs={$deadLineTs}  ";

                            echo "EXEC:\n{$cmd} {$logType} {$logFileName}\n";
                            $files[] = popen("{$cmd}  {$logType} {$logFileName}", 'w');
                        } else {
                            echo "NOT IN";
                        }
                    }

                } else {
                    echo "FALSE";
                }
                echo "\n]]]]]]]]]]]]]]]]]]]]\n";
            }
            foreach ($files as $file)
                pclose($file);
            $date = date('Y-m-d H:i:s', time());
            echo "\n$date\n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n\n\n";

        }


        /**
         * 任务一定要有key即planId，防作为统计标识         *
         * @return array
         * @throws \Exception
         */
        public static function getPlans() {
            if (file_exists(self::$hammerDir . '/config/cli/' . self::$env . '.tasks.php')) {
                return include self::$hammerDir . '/config/cli/' . self::$env . '.tasks.php';
            } else {
                throw  new \Exception('计划任务文件不存在');
            }
        }

        public static function compareDate($date, $date_) {
            if ($date_ === '*') {
                return true;
            } else {
                if (substr($date_, 0, 2) === '*/') {
                    return $date % intval(substr($date_, 2)) === 0 ? true : false;
                } else {
                    $dateInt = intval($date_);
                    return $date === $dateInt ? true : false;
                }
            }
            //return $date_ === '*' || $date == intval($date_) || strstr($date_, '*/') ? ($date % intval(str_replace('*/', '', $date_)) === 0 ? true : false) : false;
        }

        public static function getPlanRunning($planId) {
            // $planId .= '_';
            $cmdStr = "ps aux|grep php|grep 'planId=$planId'|grep -v grep|grep -v timeout|grep -v '>'";
            exec($cmdStr, $ar);
            return $ar;
        }

        public static function killTimeout($cmdLines, $nowTs) {
            // echo join("\n", $cmdLines);
            foreach ($cmdLines as $str) {
                $ar       = explode(' ', $str);
                $cmds     = [];
                $deadline = 0;
                foreach ($ar as $e) {
                    if (strlen(trim($e)))
                        $cmds[] = trim($e);
                    if (strstr($e, '--deadLineTs='))
                        $deadline = intval(str_replace('--deadLineTs=', '', $e));
                };
                $pid = intval($cmds[1]);
                $d1  = date('Y-m-d H:i:s', $deadline);
                $d2  = date('Y-m-d H:i:s', $nowTs);
                if ($d1 < $d2) {
                    $cmd = "kill {$pid}";
                    echo "\nKILLED:{$pid}\t#{$d2}>{$d1}\t#$str";
                    exec($cmd);
                }
            }
        }

        public function killPlan() {
            echo "\n killPlan\n";
            $killDir = Sys::app()->params['cli']['logDir'] . '/tasks/kill';
            foreach ([$killDir] as $dir)
                if (!file_exists($dir)) {
                    //exec("touch {$logFileName}");
                    exec("mkdir -p {$dir}");
                    exec("chmod 777 {$dir}");
                };
            $timeout = 7200;
            $endTime = time() + 3600;
            while ($endTime > time()) {
                echo "{$timeout}\n";
                $killFiles = array_slice(scandir($killDir), 2);
                foreach ($killFiles as $i => $planId) {
                    echo "{$timeout} i:{$i}/{$planId}\n";
                    $ar = CmdLauncher::getPlanRunning($planId);
                    foreach ($ar as $j => $str) {
                        echo "j:{$j} {$str}\n";
                        if ($str) {
                            preg_match('/\d+/', $str, $ar2);
                            if (count($ar2)) {
                                $pid = $ar2[0];
                                if ($pid) {
                                    $cmd = "kill {$pid}";
                                    echo "{$cmd}\n";
                                    exec("kill {$pid}", $ar3);
                                    echo join("\n", $ar3);

                                    $cmd = "rm -f {$killDir}/{$planId}";
                                    echo "{$cmd}\n";
                                    exec($cmd, $ar3);
                                    echo join("\n", $ar3);

                                }
                            }
                        }
                        echo "\n";
                    }
                    echo "\n";
                }
                //usleep(5000);
                sleep(1);
                $timeout--;
            }
        }

    }