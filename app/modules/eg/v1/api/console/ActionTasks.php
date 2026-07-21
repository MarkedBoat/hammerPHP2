<?php

    namespace modules\eg\v1\api\console;

    use hammer\web\ActionBase;
    use hammer\sys\Sys;


    class ActionTasks extends ActionBase {
        public static function getClassName() {
            return __CLASS__;
        }

        public function __construct($param = []) {
            parent::init($param);
        }

        public function run() {
            //test gitbuh
            //return [$project, $branch];
            //  $taskFile = Sys::app()->params['cli']['logDir'] . '/project/task/' . $fileName;
            // $logFile  = Sys::app()->params['cli']['logDir'] . '/project/log/' . $fileName . '.log';
            return Sys::app()->params['cli']['tasks'];

        }

    }