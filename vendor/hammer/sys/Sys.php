<?php

namespace hammer\sys;

use hammer\cli\CliApp;
use hammer\db\MCD;
use hammer\db\MysqlPdo;
use hammer\error\Interruption;
use hammer\logs\InterfaceLog;
use hammer\logs\Log;
use hammer\param\WebRequest;
use hammer\web\HttpApp;
use models\ext\tool\Printer;

/**
 * Class Sys
 * @package app\sys
 *
 * @property WebRequest $webRequest
 */
class Sys
{
    /** @var $instance static|null */
    private static $instance;
    private        $instances      = [];//实例
    private        $configKV       = [];
    private        $isDebug        = false;
    private        $magicPropertys = [];
    private        $logTraceStep   = 1;
    private        $logs           = [];
    private        $forceLog       = false;
    private        $opts           = [];
    private        $logFilename    = '';
    public         $cache          = true;
    public         $params         = [];
    private        $propertys      = [];

    public  $workMode             = '';
    private $cliRunningParam      = [];
    private $cliRunningParamTS    = 0;
    private $cliRunningParamState = 0;
    const cfgKeyRedis = 'redis';

    /** @var HttpApp| CliApp */
    private $dispatcher;

    /** @var $bizConfig  mixed */
    public $bizConfig;
    /**
     * @var $inputDataBox DataBox
     */
    private $inputDataBox;

    /**
     * @var InterfaceLog
     */
    private $logger;

    /**
     * @return static
     * @throws \Exception
     */
    public static function app()
    {
        if (is_null(self::$instance))
            throw  new \Exception('SysHelper 并未init~!');
        return self::$instance;
    }

    /**
     * @param string $mode web|cli
     *
     * @return static
     */
    public function setWorkMode(string $mode): static
    {
        $this->workMode = $mode;
        //yaf 个垃圾不开放配置文件    Yaf_Registry::get('config')->toArray() 不能用  Yaf_Config_Ini
        //$this->setBizConfig(Yaf_Registry::get('config'));
        if ($mode === 'web')
        {
            $this->debugShowOldData    = (isset($_COOKIE['showOldData']) && $_COOKIE['showOldData'] === 'yes');
            $this->debugShowTrace      = (isset($_COOKIE['showTrace']) && $_COOKIE['showTrace'] === 'yes');
            $this->debugAllowInterrupt = (isset($_COOKIE['allowInterrupt']) && $_COOKIE['allowInterrupt'] === 'yes');
            $this->debugSql            = (isset($_COOKIE['debugSql']) && $_COOKIE['debugSql'] === 'yes');

        }
        else if ($mode === 'cli')
        {

        }
        return $this;
    }

    public static function isInit()
    {
        return is_null(self::$instance) ? false : true;
    }

    /**
     * @param array $configs
     *
     * @return static
     */
    public static function init(array $configs)
    {
        if (isset($configs['password']))
        {
            fwrite(STDOUT, "----------------------------------\nEnter config password for data safe:");
            $psw = trim(fgets(STDIN));
            echo $configs['password'] === $psw ? "OK" : "FAIL!!!!!!! ";
            echo "\n----------------------------------\n";
            if ($configs['password'] !== $psw)
            {
                die;
            }
        }
        self::$instance           = new static();
        self::$instance->configKV = $configs;
        if (isset($configs['params']))
            self::$instance->params = $configs['params'];
        self::$instance->isDebug = isset(self::$instance->params['is_debug']) && self::$instance->params['is_debug'] === true;
        self::$instance->initLoger();
        ini_set('date.timezone', 'Asia/Shanghai');

        return self::$instance;
    }

    /**
     * 初始化业务的具体配置
     *
     * @return static
     */
    public function initBizConfig(): static
    {
        if (is_null($this->bizConfig))
        {
            //$this->bizConfig = Yaf_Registry::get('config');
        }
        return $this;
    }

    /**
     * 初始化业务的具体配置
     *
     * @return static
     */
    public function setBizConfig($bizConfig): static
    {
        $this->bizConfig = $bizConfig;

        return $this;
    }

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @return HttpApp| CliApp
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function getConfig()
    {
        return $this->configKV;
    }

    public function setConfig($configs)
    {
        $this->configKV = $configs;
    }

    public function addConfig($key, $config)
    {
        $this->configKV[$key] = $config;
    }

    public function addCase($key, $case)
    {
        $this->instances[$key] = $case;
    }

    public function setInputDataBox(DataBox $inputDataBox)
    {
        $this->inputDataBox = $inputDataBox;
        return $this;
    }

    /**
     * @param $redisKey
     * @return \Redis
     * @throws \Exception
     */
    public function redis($redisKey = 'default')
    {
        if (isset($this->configKV['redis'][$redisKey]))
        {
            if (!isset($this->instances['redis']))
                $this->instances['redis'] = [];
            if (!isset($this->instances['redis'][$redisKey]))
            {
                try
                {
                    $this->instances['redis'][$redisKey] = new \Redis();
                    $this->instances['redis'][$redisKey]->connect($this->configKV['redis'][$redisKey]['host'], $this->configKV['redis'][$redisKey]['port']);
                    if (isset($this->configKV['redis'][$redisKey]['password']) && $this->configKV['redis'][$redisKey]['password'] !== '')
                        $this->instances['redis'][$redisKey]->auth($this->configKV['redis'][$redisKey]['password']);
                    if (isset($this->configKV['redis'][$redisKey]['db']))
                        $this->instances['redis'][$redisKey]->select($this->configKV['redis'][$redisKey]['db']);

                } catch (\Exception $exception)
                {
                    throw  new \Exception($exception->getMessage(), $exception->getCode(), $exception);
                }

            }
        }
        else
        {
            throw  new \Exception('没有配置redis信息', 400);
        }
        return $this->instances['redis'][$redisKey];
    }

    /**
     * @return MCD
     * @throws \Exception
     */
    public function memcached()
    {
        if (isset($this->instances['memcached']))
            return $this->instances['memcached'];
        if (isset($this->configKV['memcached']))
        {
            if (!isset($this->instances['memcached']))
            {
                try
                {
                    $this->instances['memcached'] = new MCD($this->configKV['memcached']);
                } catch (\Exception $exception)
                {
                    throw  new \Exception($exception->getMessage(), $exception->getCode(), '');
                }
            }
        }
        else
        {
            throw  new \Exception('没有配置memcached信息', 400);
        }
        return $this->instances['memcached'];
    }


    /**
     * @param $dbKey
     * @return MysqlPdo
     * @throws \Exception
     */
    public function db($dbKey)
    {
        if (isset($this->configKV['db'][$dbKey]))
        {
            if (!isset($this->instances['db']))
                $this->instances['db'] = [];
            if (!isset($this->instances['db'][$dbKey]))
            {
                $this->instances['db'][$dbKey] = MysqlPdo::configDb($this->configKV['db'][$dbKey]);
            }
        }
        else
        {
            Sys::app()->interruption()->setMsg('没有配置信息:db>' . $dbKey . '>' . ENV_NAME)->outError();
        }
        return $this->instances['db'][$dbKey];
    }

    public function unsetDb($dbKey)
    {
        if (isset($this->configKV['db'][$dbKey]))
        {
            if (!isset($this->instances['db']))
                $this->instances['db'] = [];
            if (isset($this->instances['db'][$dbKey]))
            {
                $this->instances['db'][$dbKey] = null;
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            Sys::app()->interruption()->setMsg('没有配置信息:db>' . $dbKey . '>' . ENV_NAME)->outError();
        }
    }


    public function isDebug()
    {
        return $this->isDebug;
    }

    /**
     * CLI 直接 设置  true   CGI 使用默认的看配置
     *
     * @param $status
     */
    public function setDebug($status)
    {
        $this->isDebug = $status;
        if ($status)
        {
            self::app()->logData([$status], 'setDebug', true);
        }
    }

    /**
     * @param bool $status
     *
     * @return static
     */
    public function setForceLog($status = true)
    {
        $this->forceLog = $status;
        return $this;
    }


    public function setLogTraceStep($step = 1)
    {
        $this->logTraceStep = $step;
    }

    /**
     * @throws Exception
     */
    public function logData($data, $title = false, $trace = false, $logFile = false)
    {
        if ($this->isDebug === false && $this->forceLog === false)
            return false;
        $this->forceLog = false;
        if ($trace)
        {
            $steps = debug_backtrace();
            $step  = [];
            foreach ($steps as $step)
            {
                if ($step['function'] === 'logData')
                {
                    break;
                }
            }
            // $step = debug_backtrace()[$deep];
            self::app()->log((($title ? $title : '') . '                     #==>' . $step['class'] . '->' . $step['function'] . '() #') . "#             " . $step['file'] . ':' . $step['line'] . "     ",

                $data,);
        }
        else
        {
            self::app()->log($title, $data);
        }


    }

    /**
     * @return Interruption
     */
    public function interruption()
    {
        if (!isset($this->instances['interruption']))
            $this->instances['interruption'] = new Interruption();
        return $this->instances['interruption'];
    }


    /**
     * @throws Exception
     */
    public function addError($data, $title = '', $trace = true): ?bool
    {
        return $this->addLog($data, $title ? "__ERROR__:{$title}" : '__ERROR__', $trace = true, 'addError');
    }


    public function __get($name)
    {
        if (!isset($this->magicPropertys[$name]))
        {
            switch ($name)
            {
                case 'webRequest':
                    $this->magicPropertys['webRequest'] = new WebRequest();
                    break;
            }
        }
        return $this->magicPropertys[$name] ?? false;
    }

    /**
     * @return Printer
     */
    public function getPrinter()
    {
        return $this->instances['printer'];
    }

    public function initPrinter()
    {
        $this->addCase('printer', new Printer());
    }

    /**
     * 获取 选项值
     *
     * @param string $opt_key 选项值，请用正向描述的  比如  no_cache
     *
     * @return bool|mixed
     */
    public function getOptValue($opt_key)
    {
        return isset($this->opts[$opt_key]) ? $this->opts[$opt_key] : false;
    }

    public function setOpts($map)
    {
        $this->opts = $map;
        return $this;
    }

    public function addOpt($key, $val)
    {
        $this->opts[$key] = $val;
        return $this;
    }


    /**
     * 主动退出，调用 预期错误处理方法，会直接 die/exit那种
     * @param $code
     * @param $msg
     * @param $data
     * @param array $debugData
     */
    public function outError($code, $msg, $data, $debugData = [])
    {
        $this->dispatcher->expectError($code, $msg, $data, $debugData);
    }


    /**
     * 初始化loger
     * @return void
     */
    private function initLoger()
    {
        if (empty($this->logger))
        {

            $this->logger = new $this->configKV['log']['classname']();
            if (isset($this->configKV['log']['fullDir']) && $this->configKV['log']['fullDir'])
            {
                $this->logger->setDir($this->configKV['log']['fullDir']);
            }
            else
            {
                if (isset($this->configKV['log']['dir']) && $this->configKV['log']['dir'])
                {
                    $this->logger->setDir(__HAMMER_DIR__ . $this->configKV['log']['dir']);
                }
                else
                {
                    $this->logger->setDir(__HAMMER_DIR__ . '/runtimes/log');
                }
            }
            $this->logger->setlogDatePathStyle($this->configKV['log']['datePathStyle'] ?? 'Ymd');
            if (isset($this->configKV['log']['dataStyle']))
            {
                $this->logger->setDataStyle($this->configKV['log']['dataStyle']);
            }
        }
    }

    /**
     * @return InterfaceLog
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function letMemLogging($sta)
    {
        $this->logger->letMemLogging($sta);
        return $this;
    }

    public function letFileLogging($sta)
    {
        $this->logger->letFileLogging($sta);
        return $this;
    }

    public function log($v1defText, $v2defData = false)
    {

        if ($this->isDebug)
        {
            $this->logger->letMemLogging(true);
        }
        if ($v2defData === false)
        {
            if (is_string($v1defText))
            {
                $this->logger->log($v1orText);
            }
            else if (is_array($v1defText) || is_object($v1defText))
            {
                $this->logger->log('', $v1orText);
            }
            else
            {
                $this->logger->log('', [$v1defText]);
            }
        }
        else
        {
            if (is_string($v1defText))
            {
                if (is_array($v2defData) || is_object($v2defData))
                {
                    $this->logger->log($v1defText, $v2defData);
                }
                else
                {
                    $this->logger->log($v1defText, [$v2defData]);
                }
            }
            else if (is_array($v1defText) || is_object($v1defText))
            {
                if (is_string($v1defText))
                {
                    $this->logger->log($v1defText . $v2defData);
                }
                else
                {
                    $this->logger->log('', [$v1defText, $v2defData]);
                }
            }
            else
            {
                $this->logger->log('', [$v1defText, $v2defData]);
            }
        }

    }

    public function getLogs()
    {
        return $this->logger->getMemLogs();
    }
}

