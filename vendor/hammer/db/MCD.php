<?php

    namespace hammer\db;


    use hammer\sys\Sys;

    /***
     * Memcached 缓存
     * Class MCD
     * @package models\db
     */
    class MCD {
        private static $serverCnt = 0;
        /**
         * @var \Memcached
         */
        private $mem = null;


        public function __construct(array $servers) {
            $this->mem = new \Memcached();
            $this->mem->addservers($servers);
            self::$serverCnt = count($servers);
            if (self::$serverCnt == 0)
                Sys::app()->interruption()->setMsg('memcached 服务器配置配置有误')->setCode('memcached_servers_count_0')->outError();
        }


        public function getKeyIndex($key) {
            $num = ord(substr($key, -1));
            return $num % self::$serverCnt;
        }

        /**
         * @param $key
         * @param $val
         * @param $ttl
         * @return bool
         */
        public function set($key, $val, $ttl = 0) {
            $serverIndex = $this->getKeyIndex($key);
            return $this->mem->setByKey($serverIndex, $key, $val, $ttl > 0 ? (time() + $ttl) : null);

        }

        /**
         * @param $key
         * @return mixed
         */
        public function get($key) {
            $serverIndex = $this->getKeyIndex($key);
            $val         = $this->mem->getByKey($serverIndex, $key);
            return $val;
        }

        /**
         * @param $key
         * @param int $ttl
         * @return bool
         */
        public function del($key, $ttl = 0) {
            $serverIndex = $this->getKeyIndex($key);
            $val         = $this->mem->deleteByKey($serverIndex, $key, $ttl);
            return $val;
        }

    }

