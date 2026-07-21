<?php

    namespace hammer\db;

    //use \cli\CConsole;

    use models\common;
    use models\Sys;

    class Db {
        private       $runner    = '';
        private       $command   = null;
        private       $method    = null;
        private       $args      = array();
        public        $isAlive   = true;
        public static $dbs       = [];
        public        $db        = null;
        public static $dbKeyName = '';

        public static function db() {
            return new Db();
        }

        /**
         * @param $dbKey
         * @return MysqlPdo
         * @throws \Exception
         */
        function __get($dbKey) {
            $dbConfigList = Sys::$configs['db'];
            if (!isset(Hammer::$dbs[$dbKey])) {
                if (isset($dbConfigList[$dbKey])) {
                    Hammer::$dbs[$dbKey] = MysqlPdo::configDb($dbConfigList[$dbKey]);
                } else {
                    throw new \Exception('could not find config ' . $dbKey . "\n" . var_export($dbConfigList, true) . "\n");
                }
            }
            return Hammer::$dbs[$dbKey];
        }


        private static function __getDbConfig() {
            // return include __WEBROOT__ . '/config/db.php';
            return include Sys::getCfgDir() . 'Db.php';
        }

        function __construct() {

        }

        function __destruct() {
            /*
            // TODO: Implement __destruct() method.
            foreach ($this->connections as $dbKey => $db) {
                $this->connections[$dbKey] = null;
                unset($this->connections[$dbKey]);
            }
            */
            foreach (static::$dbs as $dbKey => $db)
                unset(static::$dbs[$dbKey]);

        }

        function init() {

        }
    }
