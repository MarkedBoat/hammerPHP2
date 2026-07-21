<?php

    namespace hammer\param;


    use hammer\sys\Sys;

    class Args {
        const typeString = 'string';
        const typeInt    = 'int';
        const typeArray  = 'array';
        const typeBool   = 'bool';

        private $__data = [];

        public function __construct(array $array) {
            $this->__data = $array;
        }

        public function add($key, $val) {
            $this->__data[$key] = $val;
        }

        public function getInt($key) {
            return $this->__getExisted($key, self::typeInt);
        }

        public function getNotEmptyInt($key) {
            return $this->__getNotNull($key, self::typeInt);
        }

        public function tryGetInt($key, $allNull = false) {
            return $this->__tryGet($key, self::typeInt, $allNull);
        }


        public function getString($key) {
            return $this->__getExisted($key, self::typeString);
        }

        public function getNotEmptyString($key) {
            return $this->__getNotNull($key, self::typeString);
        }

        public function tryGetString($key, $allNull = false) {
            return $this->__tryGet($key, self::typeString, $allNull);
        }

        public function getBool($key) {
            return $this->__getExisted($key, self::typeBool);
        }

        public function getNotEmptyBool($key) {
            return $this->__getNotNull($key, self::typeBool);
        }

        public function tryGetBool($key, $allNull = false) {
            return $this->__tryGet($key, self::typeBool, $allNull);
        }


        public function getArray($key) {
            return $this->__getExisted($key, self::typeArray);
        }

        public function getNotEmptyArray($key) {
            return $this->__getNotNull($key, self::typeArray);
        }

        public function tryGetArray($key, $allNull = false) {
            return $this->__tryGet($key, self::typeArray, $allNull);
        }


        public function getData() {
            return $this->__data;
        }

        /**
         * 获取版本
         * @param int $v1
         * @param int $v2
         * @param int $v3
         * @param string $key
         * @param bool $necessary
         * @return bool
         * @throws \Exception
         */
        public function getVersion(&$v1 = 0, &$v2 = 0, &$v3 = 0, $key = 'version', $necessary = false) {
            $version = $necessary ? Param::getNotEmptyString($this->__data, $key) : Param::tryGetString($this->__data, $key);
            if (empty($version))
                return false;
            $vers = explode('.', $version);
            if (count($vers) < 3) {
                if ($necessary === false) {
                    return false;
                } else {
                    throw  new \Exception('信息错误', 400);
                }
            }
            $v1 = $vers[0];
            $v2 = $vers[1];
            $v3 = $vers[2];
            return true;
        }

        public function isTimeout($range, $now = 0) {
            if ($now === 0)
                $now = time();
            if ($this->getNotEmptyString('timestamp') < ($now - $range))
                Sys::app()->interruption()->setMsg('参数超时')->setCode('timestamp_expired')->outError();

        }

        public static function getQueryParam($query) {
            $array = json_decode($query, true);
            if (!is_array($array))
                Sys::app()->interruption()->setMsg('query解析失败')->setCode('json_decode_error')->outError();
            return new Args($array);
        }

        private function __tryGet($key, $type, $allNull = false) {
            if (isset($this->__data[$key])) {
                return $this->__conv($this->__data[$key], $type, $key);
            } else {
                if ($allNull) {
                    return null;
                } else {
                    return $this->__getNullVal($type);
                }

            }
        }

        private function __getExisted($key, $type) {
            if (isset($this->__data[$key])) {
                $val = $this->__conv($this->__data[$key], $type, $key);
                return $val;
            } else {
                throw  new \Exception('缺少参数:' . $key, 400);
            }
        }

        private function __getNotNull($key, $type) {
            if (isset($this->__data[$key])) {
                $val = $this->__conv($this->__data[$key], $type, $key);
                if ($val === $this->__getNullVal($type))
                    throw  new \Exception('参数不能为空:' . $key, 400);
                return $val;
            } else {
                throw  new \Exception('缺少参数:' . $key, 400);
            }
        }

        private function __getNullVal($type) {
            switch ($type) {
                case self::typeString:
                    $val = '';
                    break;
                case self::typeInt:
                    $val = 0;
                    break;
                case self::typeBool:
                    $val = false;
                    break;
                case self::typeArray:
                    $val = [];
                    break;
                default:
                    throw  new \Exception('无此类型', 400);
                    break;
            };
            return $val;
        }

        private function __conv($val, $type, $key = '') {
            switch ($type) {
                case self::typeString:
                    $val = trim(strval($val));
                    break;
                case self::typeInt:
                    $val = intval($val);
                    break;
                case self::typeBool:
                    if (!is_bool($val))
                        Sys::app()->interruption()->setMsg('参数不是布尔类型' . $key)->outError();
                    break;
                case self::typeArray:
                    if (!is_array($val))
                        Sys::app()->interruption()->setMsg('参数不是数组' . $key)->outError();
                    break;
                default:
                    throw  new \Exception('无此类型', 400);
                    break;
            };
            return $val;
        }


    }

