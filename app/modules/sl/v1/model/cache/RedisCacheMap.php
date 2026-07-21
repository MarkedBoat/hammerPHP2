<?php

    namespace modules\sl\v1\model\cache;

    use hammer\sys\Sys;

    class RedisCacheMap extends RedisCacheBase {
        /**
         * @param $cacheKey
         * @return RedisCacheMap
         */
        public static function model($cacheKey) {
            return self::configCache($cacheKey, null);
        }

        public function getByKey($key) {
            return $this->__getForce($key);
        }

        public function __getForce($key) {
            if ($this->map === false) {
                $val = $this->redis->get($key);
            } else {
                $val = $this->redis->hGet($this->map, $key);
            }
            switch ($this->valType) {
                case self::valTypeArray:
                    $val = json_decode($val, true);
                    if (!is_array($val))
                        $val = null;
                    break;
                case self::valTypeObject:
                    $val = json_decode($val);
                    if (!is_array($val))
                        $val = null;
                    break;
                case self::valTypeInt:
                    if ($val === false)
                        $val = null; else $val = intval($val);
                    break;
                case self::valTypeBool:
                    if ($val === '1')
                        $val = true;
                    break;
                default:
                    if ($val === false)
                        $val = null;
                    break;
            }
            return $val;
        }

        public function getAll() {
            if ($this->map === false)
                Sys::app()->interruption()->setCode('cacheMap_not_exist')->setMsg('服务器错误')->outError();
            return $this->redis->hGetAll($this->map);

        }


        public function setByKey($key, $val) {
            $this->__setForce($key, $val);
        }

        private function __setForce($key, $val) {
            switch ($this->valType) {
                case self::valTypeArray:
                    $val = json_encode($val);
                    break;
                case self::valTypeObject:
                    $val = json_encode($val);
                    break;
                case self::valTypeInt:
                    $val = intval($val);
                    break;
                case self::valTypeBool:
                    if (in_array($val, [1, true, '1', 'true'],true))
                        $val = true;
                    break;
                case self::valTypeString:
                    break;
            }
            if ($this->map === false) {
                $this->redis->set($key, $val, $this->ttl);
            } else {
                $this->redis->hSet($this->map, $key, $val);
            }
        }


        /**
         * 指定key 删除
         * @param $key
         * @return int
         */
        public function delByKey($key) {
            return $this->__delForce($key);
        }

        /**
         * @param $key
         * @return int
         */
        private function __delForce($key) {
            if ($this->map === false) {
                return $this->redis->del($key);
            } else {
                return $this->redis->hDel($this->map, $key);
            }
        }


    }