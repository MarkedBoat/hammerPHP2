<?php

    namespace modules\sl\v1\model;

    use hammer\db\DbModel;
    use hammer\sys\Sys;
    use modules\sl\v1\dao\OperDao;
    use modules\sl\v1\model\cache\RedisCache;


    class ModelBase {

        protected $allAttrKeys = ['id', 'open_id', 'open_src', 'cdate', 'ip', 'nickname', 'avatar'];

        /**
         * @var OperDao
         */
        protected $dao        = null;
        private   $__daoAttrs = [];
        /**
         * @var RedisCache
         */
        protected $cache = null;

        protected $pkAttrsCacheType = '';


        public function __construct1(OperDao $dao) {
            $this->dao = $dao;
        }

        /**
         * @param DbModel $dao
         * @return $this
         */
        public static function modelByDao(DbModel $dao) {
            $calledClass = get_called_class();
            $model       = new $calledClass($calledClass);
            $model->dao  = $dao;
            $model->loadByAttrs($dao->getAttributes());
            return $model;
        }


        public function getDao() {
            return $this->dao;
        }

        /**
         * @param $attrs
         * @return $this
         */
        public function loadByAttrs($attrs) {
            foreach ($attrs as $key => $val)
                if (in_array($key, $this->allAttrKeys))
                    $this->$key = $val;
            $this->__daoAttrs = $attrs;
            $this->getCache()->set($attrs);
            return $this;
        }

        public function reloadDaoAttrs() {
            $this->loadByAttrs($this->dao->getAttributes());
        }

        public function getDaoAttrs() {
            return $this->__daoAttrs;
        }

        /**
         * @return RedisCache
         */
        public function getCache() {
            if (is_null($this->cache)) {
                if (is_null($this->getDao()))
                    Sys::app()->interruption()->setCode('code_error_')->setMsg('系统错误')->outError();
                $this->cache = RedisCache::model($this->pkAttrsCacheType, $this->id);
            }
            return $this->cache;
        }


    }