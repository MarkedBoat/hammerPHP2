<?php

    namespace modules\sl\v1\model\cache;

    use hammer\sys\Sys;

    class RedisCache extends RedisCacheBase {


        /**
         * @param $cacheType
         * @param $key
         * @return RedisCache
         */
        public static function model($cacheType, $key) {
            return self::configCache($cacheType, $key);
        }

        /**
         * @return bool|mixed|null|string
         */
        public function get() {
            if (Sys::app()->cache === false)
                return null;
            if ($this->key === false)
                Sys::app()->interruption()->setCode('cache_error_key_is_null')->setMsg('服务器错误')->outError();
            return $this->getForce($this->key);
        }

        /**
         * 覆盖缓存
         * @param $val
         */
        public function set($val) {
            $this->setForce($this->key, $val);
        }

        /**
         * @return int
         */
        public function del() {
            if ($this->key === false)
                Sys::app()->interruption()->setCode('cache_error_key_is_null')->setMsg('服务器错误')->outError();
            return $this->delForce($this->key);
        }

        public function delMap() {
            $keys = $this->redis->hKeys($this->map);
            array_unshift($keys, $this->map);
            $r = call_user_func_array([$this->redis, 'hDel'], $keys);
            Sys::app()->logData([$r, $keys], $this->map);
        }

        public function tryLock() {
            $r = $this->redis->setnx($this->key, 1);
            if ($r) {
                Sys::app()->redis()->setTimeout($this->key, $this->ttl);//60秒安全锁
                return true;
            } else {
                return false;
            }
        }

        public function unlock() {

        }


    }