<?php

namespace hammer\param;

class tmp
{
    const typeString = 'string';
    const typeInt    = 'int';
    const typeArray  = 'array';
    const typeBool   = 'bool';

    private $__core_array = [];

    private $exception_class = '';
    private $execSettingInfo = [];

    public function __construct(array $array)
    {
        $this->__core_array = $array;
    }

    /**
     * @param $msg
     *
     * @return static
     */
    public function setEmptyErrorMsg($msg)
    {
        $this->execSettingInfo['empty_error_msg'] = $msg;
        return $this;
    }

    /**
     * @param $msg
     *
     * @return static
     */
    public function setNotExistErrorMsg($msg)
    {
        $this->execSettingInfo['not_exist_error_msg'] = $msg;
        return $this;
    }

    public function setErrorMsg($msg)
    {
        $this->execSettingInfo['empty_error_msg']     = $msg;
        $this->execSettingInfo['not_exist_error_msg'] = $msg;
        return $this;
    }

    public function checkLength($minLength, $maxLength, $errorMsg = '')
    {
        $this->execSettingInfo['checkLength'] = [
            'min' => $minLength,
            'max' => $maxLength,
            // 'errMsg' => $errorMsg
        ];
        if ($errorMsg)
        {
            $this->execSettingInfo['checkLength']['errMsg'] = $errorMsg;
        }
        return $this;
    }

    public function checkInVals($vals, $errorMsg = '')
    {
        $this->execSettingInfo['checkInVals'] = [
            'vals' => $vals,
            // 'errMsg' => $errorMsg
        ];
        if ($errorMsg)
        {
            $this->execSettingInfo['checkInVals']['errMsg'] = $errorMsg;
        }
        return $this;
    }

    private function _verifyVal($val)
    {
        if (isset($this->execSettingInfo['checkLength']))
        {
            $len = strlen($val);
            if ($len < $this->execSettingInfo['checkLength']['min'] || $len > $this->execSettingInfo['checkLength']['max'])
            {
                SysHelper::app()->logData($val, 'checkLength_errMsg', false, false);

                throw new Exception($this->execSettingInfo['checkLength']['errMsg'] ?? "{$this->execSettingInfo['key']} length not in {$this->execSettingInfo['checkLength']['min']}-{$this->execSettingInfo['checkLength']['max']}}");
            }
        }
        if (isset($this->execSettingInfo['checkInVals']))
        {
            if (!in_array($val, $this->execSettingInfo['checkInVals']['vals']))
            {
                throw new Exception($this->execSettingInfo['checkInVals']['errMsg'] ?? "{$this->execSettingInfo['key']} not in vals");
            }
        }
        $this->execSettingInfo = [];
    }

    /**
     * @param $query
     *
     * @return static|false
     * @throws \Exception
     */
    public static function constructFromJsonString($query)
    {
        if (!is_string($query))
        {
            return false;
        }
        $array = json_decode($query, true);
        if (!is_array($array))
        {
            return false;
        }
        return new static($array);
    }

    public static function constructFromREQUEST(): Param_DataBox
    {
        return new static($_REQUEST);
    }

    public static function constructFromGET(): Param_DataBox
    {
        return new static($_GET);
    }

    public static function constructFromPOST(): Param_DataBox
    {
        return new static($_POST);
    }

    /**
     * @throws Exception
     */
    public static function constructFromPostBody(): Param_DataBox
    {
        return self::constructFromJsonString(self::getRawPostDataString());
    }

    /**
     * HTTP_RAW_POST_DATA
     *
     * @return false|string
     */
    public static function getRawPostDataString()
    {
        return file_get_contents("php://input");
    }


    /**
     * @param $classname
     *
     * @return static
     */
    public function setExcepionClass($classname): Param_DataBox
    {
        $this->exception_class = $classname;
        return $this;
    }


    public function add($key, $val)
    {
        $this->__core_array[$key] = $val;
    }

    /**
     * @throws Exception
     */
    public function getExistVal($key, $type)
    {
        return $this->__getExisted($key, $type);
    }

    /**
     * @throws Exception
     */
    public function getExistedInt($key)
    {
        return $this->__getExisted($key, self::typeInt);
    }

    /**
     * @throws Exception
     */
    public function getNotEmptyInt($key)
    {
        return $this->__getNotEmpty($key, self::typeInt);
    }

    /**
     * @throws Exception
     */
    public function tryGetInt($key, $allNull = false)
    {
        return $this->__tryGet($key, self::typeInt, $allNull);
    }


    /**
     * @throws Exception
     */
    public function getExistedString($key)
    {
        return $this->__getExisted($key, self::typeString);
    }

    /**
     * @throws Exception
     */
    public function getNotEmptyString($key)
    {
        return $this->__getNotEmpty($key, self::typeString);
    }

    /**
     * @throws Exception
     */
    public function tryGetString($key, $allNull = false)
    {
        return $this->__tryGet($key, self::typeString, $allNull);
    }

    /**
     * @throws Exception
     */
    public function getExistedBool($key)
    {
        return $this->__getExisted($key, self::typeBool);
    }

    /**
     * @throws Exception
     */
    public function getNotEmptyBool($key)
    {
        return $this->__getNotEmpty($key, self::typeBool);
    }

    /**
     * @throws Exception
     */
    public function tryGetBool($key, $allNull = false)
    {
        return $this->__tryGet($key, self::typeBool, $allNull);
    }


    /**
     * @throws Exception
     */
    public function getExistedArray($key)
    {
        return $this->__getExisted($key, self::typeArray);
    }

    /**
     * @param $key
     *
     * @return array
     * @throws \Exception
     */
    public function getNotEmptyArray($key): array
    {
        return $this->__getNotEmpty($key, self::typeArray);
    }

    /**
     * @throws Exception
     */
    public function tryGetArray($key, $allNull = false)
    {
        return $this->__tryGet($key, self::typeArray, $allNull);
    }


    public function getCoreArray(): array
    {
        return $this->__core_array;
    }


    public function isTimeout($param_timestamp, $seconds, $now = 0): bool
    {
        return $param_timestamp < (($now === 0 ? time() : $now) - $seconds);
    }

    /**
     * 尝试获取，不做任何转换
     *
     * @param $key
     *
     * @return string|int|array|bool|null
     */
    public function tryGet($key)
    {
        return $this->__core_array[$key] ?? null;
    }

    /**
     * @throws Exception
     */
    private function __tryGet($key, $type, $allNull = false)
    {
        if (isset($this->__core_array[$key]))
        {
            return $this->__conv($this->__core_array[$key], $type, $key);
        }
        else
        {
            if ($allNull)
            {
                return null;
            }
            else
            {
                return $this->__getEmptyVal($type);
            }

        }
    }

    /**
     * @throws Exception
     */
    private function __getExisted($key, $type)
    {
        if (isset($this->__core_array[$key]))
        {
            return $this->__conv($this->__core_array[$key], $type, $key);
        }
        else
        {
            $err_msg               = $this->execSettingInfo['not_exist_error_msg'] ?? ('缺少参数:' . $key);
            $this->execSettingInfo = [];
            if ($this->exception_class === '')
            {
                throw  new \Exception($err_msg, 400);
            }
            else
            {
                $class = $this->exception_class;
                throw new $class($err_msg, 400);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function __getNotEmpty($key, $type)
    {
        $this->execSettingInfo['key']  = $key;
        $this->execSettingInfo['type'] = $type;
        if (isset($this->__core_array[$key]))
        {
            $err_msg = $this->execSettingInfo['empty_error_msg'] ?? ('参数不能为空:' . $key);
            $val     = $this->__conv($this->__core_array[$key], $type, $key);
            if ($val === $this->__getEmptyVal($type))
            {
                $this->execSettingInfo = [];
                if ($this->exception_class === '')
                {
                    throw  new \Exception($err_msg, 400);
                }
                else
                {
                    $class = $this->exception_class;
                    throw new $class($err_msg, 400);
                }
            }
            //$this->_verifyVal($val);
            return $val;
        }
        else
        {
            $err_msg               = $this->execSettingInfo['not_exist_error_msg'] ?? ('缺少参数:' . $key);
            $this->execSettingInfo = [];
            if ($this->exception_class === '')
            {
                throw  new \Exception($err_msg, 400);
            }
            else
            {
                $class = $this->exception_class;
                throw new $class($err_msg, 400);
            }
        }
    }

    /**
     * @param $type
     *
     * @return array|bool|int|string
     * @throws \Exception
     */
    private function __getEmptyVal($type)
    {
        switch ($type)
        {
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
                if ($this->exception_class === '')
                {
                    throw  new \Exception('无此类型', 400);
                }
                else
                {
                    $class = $this->exception_class;
                    throw new $class('无此类型', 400);
                }
                break;
        };
        return $val;
    }

    /**
     * @throws Exception
     */
    private function __conv($val, $type, $key = '')
    {
        switch ($type)
        {
            case self::typeString:
                $val = trim(strval($val));
                break;
            case self::typeInt:
                $val = intval($val);
                break;
            case self::typeBool:
                if (in_array($val, ['yes', 'true']))
                {
                    $val = true;
                }
                else
                {
                    if (!is_bool($val))
                        throw  new \Exception('参数不是布尔类型', 400);
                }

                break;
            case self::typeArray:
                if (!is_array($val))
                {
                    var_dump($val);
                    throw  new \Exception('参数不是数组', 400);

                }
                break;
            default:
                if ($this->exception_class === '')
                {
                    throw  new \Exception('无此类型', 400);
                }
                else
                {
                    $class = $this->exception_class;
                    throw new $class('无此类型', 400);
                }
                break;
        };
        $this->_verifyVal($val);
        return $val;
    }


    /**
     * 尝试获取某个参数，如果没有，则返回默认值
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function tryGetVal(string $key, $default)
    {
        if (isset($this->__core_array[$key]))
        {
            return $this->__core_array[$key];
        }
        else
        {
            return $default;
        }
    }


    /**
     * 查看是否在某个返回,不在直接报错,类型就不用规定了，因为$vals 有了限制,所以很清楚预期
     *
     * @param array $vals
     * @param string $key
     * @param bool $throw_error
     *
     * @return mixed
     * @throws \Exception
     */
    public function getInVals(string $key, array $vals, bool $throw_error = true)
    {
        $val = $this->tryGetVal($key, null);
        if (in_array($val, $vals, true))
        {
            return $val;
        }
        else
        {
            if ($throw_error)
            {
                if ($this->exception_class === '')
                {
                    throw  new \Exception("param [{$key}] not in vals");
                }
                else
                {
                    $class = $this->exception_class;
                    throw new $class("param [{$key}] not in vals");
                }
            }
            else
            {
                return null;
            }
        }
    }


}

