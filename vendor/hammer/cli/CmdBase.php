<?php

    /**
     * Created by PhpStorm.
     * User: markedboat
     * Date: 2018/7/20
     * Time: 11:01
     */

    namespace hammer\cli;

    use hammer\param\DataBox;
    use hammer\sys\Sys;

    //更新计划凭借代码方法 sh ~/kinglone.sh "/data/git-webroot/api-htdocs/CLI/" "origin/master"
    // 简易计划任务操作  sudo chmod +x /data/git-webroot/api-htdocs/CLI/itfc/app cmd:/data/git-webroot/api-htdocs/CLI/itfc/app bftv.user.service.renew starter --service=kids --planId=renewKidsMember --env=prod --deadLineTs=1550222461
    class CmdBase {
        public static $logDir     = '';
        public static $shFileName = '';
        public static $hammerDir  = '';
        public static $env        = '';
        protected     $inputBox     = null;
        private       $__planId   = '';

        public static function getClassName()
        {
            return static::class;
        }

        public function __construct($param = []) {
            $this->inputBox = new DataBox($param);
            static::init($param);
        }

        public function init() {
            // $this->inputBox = new DataBox($param);
            // $this->__apiName = $this->args->tryGetString('method');
        }


        public function run() {

            // sleep(1);
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

        /**
         * 获取当前命令状态
         * @return bool|string
         */
        public function getCurrentStatus() {
            return Sys::app()->redis('default')->get('cmd_current_status_' . $this->__planId);
        }

        /**
         *
         * @return bool
         */
        public function isCmdShutdown() {
            $shutdownStatus = Sys::app()->redis('default')->get('cmd_is_shutdown_' . $this->__planId);
            return $shutdownStatus === 'yes' ? true : false;
        }


    }