<?php

    namespace hammer\error;

    use hammer\sys\Sys;

    class Interruption {
        const ERROR = 400;
        private $__code      = 'ok';
        private $__msg       = '';
        private $__debugMsg  = [];
        private $__debugData = [];
        private $__logs      = [];
        private $__isThrower = false;
        private $__isWorking = false;


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
            $this->__code = $code;
            return $this;
        }

        public function getCode() {
            return $this->__code;
        }

        /**
         * @param $msg
         * @return Interruption
         */
        public function setMsg($msg) {
            $this->__msg = $msg;
            return $this;
        }

        /**
         * @return string
         */
        public function getMsg() {
            return $this->__msg;
        }

        /**
         * @param $debugMsg
         * @return Interruption
         */
        public function setDebugMsg($debugMsg) {
            $this->__debugMsg = $debugMsg;
            return $this;
        }

        public function getDebugMsg() {
            return $this->__debugMsg;
        }

        /**
         * @param $debugData
         * @return Interruption
         */
        public function setDebugData($debugData) {
            $this->__debugData = $debugData;
            return $this;
        }

        public function getDebugData() {
            return $this->__debugData;
        }

        /**
         * @throws \Exception
         */
        public function outError() {
            if ($this->__code === 'ok')
                $this->__code = 'error';
            $this->__isThrower = true;
            throw  new \Exception($this->getMsg(), 400);
        }

        public function isThrower() {
            return $this->__isThrower;
        }

        public function isWorking() {
            return $this->__isWorking;
        }

        public function setWorking() {
            $this->__isWorking = true;
        }

        /**
         * @param $info
         */
        public function logInfo($info) {
            $this->__logs[] = $info;
        }

        /**
         * @return array
         */
        public function getLogs() {
            return $this->__logs;
        }

    }

