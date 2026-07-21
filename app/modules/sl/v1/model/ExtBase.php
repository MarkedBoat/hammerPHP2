<?php

    namespace modules\sl\v1\model;


    use hammer\db\DbModel;
    use hammer\param\Param;
    use hammer\sys\Sys;
    use modules\sl\v1\model\cache\RedisCache;

    abstract class ExtBase {


        /**
         * @var RedisCache|null
         */
        private   $__cache      = null;
        protected $extTableName = '';
        protected $indexField   = '';
        protected $pkField      = '';
        /**
         * @var DbModel
         */
        protected     $dao       = null;
        protected     $cacheData = null;
        protected     $attrData  = [];
        public static $rules     = [];

        const attrUndefine = 1;
        const attrExt      = 2;
        const attrRecord   = 3;//需要保留记录

        /**
         * @return RedisCache|null
         */
        public function getCache() {
            return $this->__cache;
        }


        abstract public function getAttrsRule();

        public function __set($name, $value) {
            if (!strstr($name, '_'))
                Sys::app()->interruption()->setMsg('extBase 调用方法不当')->outError();
            $this->attrData[substr($name, 1)] = $value;
        }

        /**
         * @return array|int|null|string|bool
         */
        public function getCacheData() {
            return $this->cacheData;
        }

        protected function analyzeRows($table) {
            $cacheData = [];
            $rule      = $this->getAttrsRule();
            foreach ($rule as $key => $opt)
                $cacheData[$key] = $opt['default'];
            foreach ($table as $row) {
                if ($row['sta'] === '2')
                    continue;

                $val = $row['val'];
                switch ($rule[$row['attr']]['type']) {
                    case RedisCache::valTypeArray:
                        $val = json_decode($val, true);
                        if (!is_array($val))
                            $val = null;
                        break;
                    case RedisCache::valTypeObject:
                        $val = json_decode($val);
                        if (!is_array($val))
                            $val = null;
                        break;
                    case RedisCache::valTypeInt:
                        if ($val === false)
                            $val = null; else $val = intval($val);
                        break;
                    case RedisCache::valTypeBool:
                        $val = in_array($val, [1, true, '1', 'true'], true) ? true : false;
                        break;
                    default:
                        if ($val === false)
                            $val = null;
                        break;
                }
                if ($rule[$row['attr']]['max'] === 1) {
                    if ($row['sn'] === '0')
                        $cacheData[$row['attr']] = $val;
                } else {
                    //  Sys::app()->logData($row, 't2');
                    $cacheData[$row['attr']][intval($row['sn'])] = $val;
                }
            }
            //   Sys::app()->logData($cacheData, 'tttt');
            foreach ($cacheData as $attr => $val) {
                if (isset($this->$attr)) {
                    $this->$attr = $val;
                }
            }
            // Sys::app()->logData($cacheData);
            $this->cacheData = $cacheData;
            return $cacheData;
        }

        public function get($force = false) {
            if ($force) {
                $sql   = "select id,attr,sn,val,sta from {$this->extTableName} where `{$this->indexField}`=:index_val";
                $table = Sys::app()->db('sl_master')->setText($sql)->bindArray([':index_val' => $this->dao->getPkVal()])->queryAll();
                $this->getCache()->set($this->analyzeRows($table));
            } else {
                $cacheData = $this->getCache()->get();
                if (is_null($cacheData)) {
                    $sql   = "select id,attr,sn,val,sta from {$this->extTableName} where `{$this->indexField}`=:index_val";
                    $table = Sys::app()->db('sl_slave')->setText($sql)->bindArray([':index_val' => $this->dao->getPkVal()])->queryAll();
                    $this->getCache()->set($this->analyzeRows($table));
                } else {
                    foreach ($cacheData as $attr => $val) {
                        if (isset($this->$attr)) {
                            $this->$attr = $val;
                        }
                    }
                    $this->cacheData = $cacheData;
                    Sys::app()->logData('cache');
                }
            }
            return $this;
        }

        public function saveChange() {
            $kvs = [];
            foreach ($this->attrData as $attr => $val)
                $kvs[] = ['attr' => $attr, 'val' => $val];
            $this->attrData = [];
            return $this->setAttrs($kvs);
        }

        public function setAttrs($kvs) {
            $rules = $this->getAttrsRule();
            $sqls  = [];
            $bind  = [
                ':index_val' => $this->dao->getPkVal(),
                ':ndate'     => date('Y-m-d H:i:s', time()),
            ];
            foreach ($kvs as $i => $kv) {
                $attr = $kv['attr'];
                if (!isset($rules[$attr]))
                    Sys::app()->interruption()->setMsg($attr . ':key没有配置')->outError();
                $val = $kv['val'];
                $sn  = isset($kv['sn']) ? intval($kv['sn']) : 0;
                if (($sn + 1) > $rules[$attr]['max'])
                    Sys::app()->interruption()->setMsg('不符合配置')->outError();
                $bind[":val_$i"] = Param::getInputVal($rules[$attr]['type'], $val);
                $sqls[]          = "insert ignore into {$this->extTableName} set `{$this->indexField}`=:index_val,attr='{$attr}',sn={$sn},val=:val_{$i},cdate=:ndate on duplicate key update val=:val_{$i},sta=1,udate=:ndate";
            }
            if (count($sqls))
                Sys::app()->db('sl_master')->setText(join(';', $sqls))->bindArray($bind)->execute();
            return $this->get(true);
        }

    }