<?php

namespace hammer\web;

use hammer\common\BaseApp;
use hammer\param\DataBox;
use hammer\sys\Sys;
use modules\sl\v1\model\Security;


/**
 * Class Action
 * @package models
 * 接口具体方法的抽你类
 */
abstract class ActionBase
{
    /**
     * @var DataBox
     */
    protected $inputBox       = null;
    public    $uniqueId       = '';
    private   $__errorMessage = '';
    private   $__errorCode    = 400;
    private   $__debugMessage = '';
    private   $__debugData    = null;
    private   $__logResult    = false;
    private   $__debug        = false;
    private   $__apiName      = '';

    protected $timeout        = 0;
    protected $hasSign        = true;
    protected $signVerifyRate = 100;
    public    $version        = 0;

    protected $inputType = 'REQUEST';// GET/POST/JSON


    /**
     * @param BaseApp $app
     */
    public function __construct($app = null)
    {
        //$this->baseInit($param);
    }


    public function beforeInit($param = [])
    {

    }

    public function init($param)
    {

    }

    public function afterInit($param = [])
    {

    }

    /**
     * ！！！！ 禁止子类调用，里面有后期静态绑定，会死循环，子类按需要用  before init /after init / 或者init作为 after init使用
     * @param $param
     * @return void
     */
    public function baseInit($param = [])
    {
        $this->beforeInit($param);
        $this->initInputBox($param);
        $this->__apiName = $this->inputBox->tryGetString('method');

        static::init($param);
        $this->afterInit($param);
    }

    public function initInputBox($param)
    {
        switch ($this->inputType)
        {
            case 'GET':
                $this->inputBox = new DataBox($_GET);
                break;
            case 'POST':
                $this->inputBox = new DataBox($_POST);
                break;
            case 'JSON':
                $this->inputBox = new DataBox($this->getRawJsonData());
                break;
            case 'REQUEST':
                $this->inputBox = new DataBox($_REQUEST);
                break;
            default:
                $this->inputBox = new DataBox($param);
        }
    }

    public function setInputBox(DataBox $Params)
    {
        $this->inputBox = $Params;
        return $this;
    }

    public function initCmd(DataBox $Params)
    {
        $this->setInputBox($Params);
        $this->run();
    }

    public static function getClassName()
    {
        return static::class;
    }

    public static function getActionName()
    {
        return static::getClassName();
    }

    /**
     * @return DataBox
     */
    public function getInputBox()
    {
        return $this->inputBox;
    }


    public function getRawData()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return array|bool
     */
    public function getRawJsonData()
    {
        return json_decode($this->getRawData(), true);
    }

    public abstract function run();


    public function setMsg($msg)
    {
        Sys::app()->interruption()->setMsg($msg);
        $this->__errorMessage = $msg;
        return $this;
    }


    public function setCode($code)
    {
        $this->__errorCode = $code;
        Sys::app()->interruption()->setCode($code);
        return $this;
    }

    /**
     * @param $debugMessage
     * @return ActionBase
     */
    public function setDebugMsg($debugMessage)
    {
        Sys::app()->interruption()->setDebugMsg($debugMessage);
        $this->__debugMessage = $debugMessage;
        return $this;
    }

    /**
     * @param $data
     * @return ActionBase
     */
    public function setDebugData($data)
    {
        Sys::app()->interruption()->setDebugData($data);
        $this->__debugData = $data;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function outError()
    {
        Sys::app()->interruption()->outError();
    }

    /**
     * 记录输出结果
     * @param bool $debug 结果中要不要debug？
     */
    public function logResult($debug = false)
    {
        $this->__logResult = true;
        $this->__debug     = $debug;
    }

    public function isLogResult()
    {

        return $this->__logResult;
    }

    public function debug()
    {
        $this->__debug = true;
    }

    public function isDebug()
    {
        return $this->inputBox->tryGetString('kldebug') == 'x' ? true : false;
        //   return $this->__debug;
    }

    public function logInfo($info, $title = false)
    {
        Sys::app()->logData($info, $title, true, 2);
    }

    public function setApiName($apiName)
    {
        $this->__apiName = $apiName;
    }

    public function getApiName()
    {
        return $this->__apiName;
    }

    public function initQueryParams()
    {
        $query = $this->getParams()->getNotEmptyString('query');

        $array = json_decode($query, true);
        if (!is_array($array))
            $this->setMsg('query解析失败')->setCode('json_decode_error')->outError();

        if ($this->hasSign)
        {
            $sign = $this->getParams()->getNotEmptyString('sign');
            if ($sign !== Sys::app()->params['debugSign'])
                $this->verifyRSASign($query, $sign);
            $array['sign'] = $sign;
            $this->logInfo('验证参数');
        }


        $array['query']   = $query;
        $array['kldebug'] = $this->getParams()->tryGetString('kldebug');
        $this->setParams(new DataBox($array));

    }

    public function verifyRSASign($query, $sign)
    {
        $result = Security::verifyRSASign($query, $sign);
        if (Sys::app()->isDebug())
        {
            Sys::app()->logData(Security::RSASign($query));
        }
        if (!$result)
            Sys::app()->interruption()->setMsg('签名错误')->setCode('sign_error')->outError();
    }

    public function getRSASign($query)
    {
        $sign = Security::RSASign($query);
        if (!$sign)
            Sys::app()->interruption()->setMsg('签名错误')->setCode('get_sign_error')->outError();
        return $sign;
    }

    public function outJsonQuery()
    {
        $ar = $_REQUEST;
        foreach (['query', 'sign', 'kldebug', 'userToken'] as $key)
            unset($ar[$key]);
        echo "\n";
        //$query = json_encode($ar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $query = json_encode($ar, JSON_UNESCAPED_UNICODE);
        $sign  = Security::RSASign($query);
        echo "query:{$query}\nsign:{$sign}";
        echo "\n";
        die;
    }


    public function initQueryArgs()
    {
        $query = $this->inputBox->getNotEmptyString('query');

        $array = json_decode($query, true);
        if (!is_array($array))
            $this->setMsg('query解析失败')->setCode('json_decode_error')->outError();

        if ($this->hasSign)
        {
            $verify = true;
            if ($this->signVerifyRate < 100)
                $verify = $this->signVerifyRate > rand(0, 100);
            if ($verify)
            {
                $sign = $this->inputBox->getNotEmptyString('sign');
                $this->logInfo('验证签名');
                $this->verifyRSASign($query, $sign);
                $array['sign'] = $sign;
            }
            else
            {
                $this->logInfo('不验证签名');
            }

        }


        $array['query']   = $query;
        $array['kldebug'] = $this->inputBox->tryGetString('kldebug');
        $this->setParams(new DataBox($array));

    }

    public function returnData()
    {

    }

    public function returnError()
    {

    }

    public function returnSuccess()
    {

    }


    /**
     * @param $query
     *
     * @return DataBox|false
     * @throws \Exception
     */
    public function getInputBoxFromJsonString($query)
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
        return new DataBox($array);
    }

    public function getInputBoxFromREQUEST(): DataBox
    {
        return new DataBox($_REQUEST);
    }

    public function getInputBoxFromGET(): DataBox
    {
        return new DataBox($_GET);
    }

    public function constructFromPOST(): DataBox
    {
        return new DataBox($_POST);
    }

    /**
     * @throws Exception
     */
    public function getInputBoxFromPostBody(): DataBox
    {
        return $this->getInputBoxFromJsonString($this->getRawPostDataString());
    }

    /**
     * HTTP_RAW_POST_DATA
     *
     * @return false|string
     */
    public function getRawPostDataString()
    {
        return file_get_contents("php://input");
    }


}

