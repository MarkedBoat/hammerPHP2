<?php

    namespace modules\eg\v1\api\console;

    use console\system\CmdLauncher;
    use hammer\web\ActionBase;
    use hammer\sys\Sys;


    class ActionKill extends ActionBase {
        public static function getClassName() {
            return __CLASS__;
        }

        public function __construct($param = []) {
            parent::init($param);
        }

        public function run() {
            $planId = $this->inputBox->getNotEmptyString('planId');
            file_put_contents(Sys::app()->params['cli']['logDir'] . '/tasks/kill/' . $planId, '');
            $data = [];
            for ($i = 0; $i < 30; $i++) {
                $data[date('Y-m-d H:i:s', time())] = CmdLauncher::getPlanRunning($planId);
                if (count($data) === 0) {
                    break;
                }
            }
            return $data;

        }

    }