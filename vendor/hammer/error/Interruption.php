<?php

    namespace hammer\error;

    use hammer\sys\Sys;

    class Interruption {
        const ERROR = 400;
        private $code      = 'ok';
        private $msg       = '';
        private $debugMsg  = [];
        private $debugData = [];
        private $logs      = [];
        private $isThrower = false;
        private $isWorking = false;


        /**
         * instantiate  a Interrupution
         * @param $msg
         * @param int $code
         * @param string $debugMsg
         * @param array $debugData
         * @return Interruption
         */
        public static function model($msg, $code = 400, $debugMsg = '', $debugData = []) {
            return Sys::app()->interruption()->setMsg($msg)->setCode($code)->setDebugMsg($debugMsg)->setDebugData($debugData);
        }


        /**
         * @param $code
         * @return Interruption
         */
        public function setCode($code) {
            $this->code = $code;
            return $this;
        }

        public function getCode() {
            return $this->code;
        }

        /**
         * @param $msg
         * @return Interruption
         */
        public function setMsg($msg) {
            $this->msg = $msg;
            return $this;
        }

        /**
         * @return string
         */
        public function getMsg() {
            return $this->msg;
        }

        /**
         * @param $debugMsg
         * @return Interruption
         */
        public function setDebugMsg($debugMsg) {
            $this->debugMsg = $debugMsg;
            return $this;
        }

        public function getDebugMsg() {
            return $this->debugMsg;
        }

        /**
         * @param $debugData
         * @return Interruption
         */
        public function setDebugData($debugData) {
            $this->debugData = $debugData;
            return $this;
        }

        public function getDebugData() {
            return $this->debugData;
        }

        /**
         * @throws \Exception
         */
        public function outError() {
            if ($this->code === 'ok')
                $this->code = 'error';
            $this->isThrower = true;
            throw  new \Exception($this->getMsg(), 400);
        }

        public function isThrower() {
            return $this->isThrower;
        }

        public function isWorking() {
            return $this->isWorking;
        }

        public function setWorking() {
            $this->isWorking = true;
        }

        /**
         * @param $info
         */
        public function logInfo($info) {
            $this->logs[] = $info;
        }

        /**
         * @return array
         */
        public function getLogs() {
            return $this->logs;
        }

    }

