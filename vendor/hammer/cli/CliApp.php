<?php

namespace hammer\cli;

use hammer\common\BaseApp;
use hammer\error\CommonError;
use hammer\error\ConsoleError;
use hammer\sys\Sys;

class CliApp extends BaseApp
{
    private $runner  = '';
    private $command = null;
    private $method  = null;
    private $args    = array();

    private static $cmd            = '';
    private static $__logFile      = '';
    private static $__errorLogFile = '';

    function __construct($args)
    {
        $cmd       = join(' ', $args);
        $date      = date('Y-m-d H:i:s', time());
        self::$cmd = $cmd;
        echo "cmd:{$date} {$cmd}\n";
        try
        {
            $env = false;
            foreach ($args as $str)
            {
                if ($env === false && substr($str, 0, 6) === '--env=')
                    $env = str_replace('--env=', '', $str);
            }
            if ($env === false)
            {
                die("\n一定要设置环境\n");
            }
            else
            {
                $config = require __APP_DIR__ . "/config/env/{$env}.php";
                Sys::init($config);
                Sys::app()->setWorkMode('cli');
            }
        } catch (\Exception $e)
        {
        }
        file_put_contents(self::getLogFile(), date('Y-m-d H:i:s', time()) . ":\t$cmd\n", FILE_APPEND);
        register_shutdown_function([$this, 'shutDown']);
        //set_error_handler([$this, 'onError']);
        try
        {

            $this->getArgs($args);

            $re       = new \ReflectionMethod($this->command, $this->method);
            $params   = $re->getParameters();
            $args     = [];
            $argNames = [];
            foreach ($params as $param)
            {
                $key        = $param->name;
                $argNames[] = $key;
                if (isset($this->args[$key]))
                {
                    $args[] = $this->args[$key];
                }
                else
                {

                }
            }
            $diff = array_diff($argNames, array_keys($this->args));
            if (count($diff))
            {
                file_put_contents(self::getErrorLogFile(), "$cmd " . join(',', $diff) . "\n", FILE_APPEND);
                var_dump($diff);
            }
            call_user_func_array(array(
                new $this->command($this->args),
                $this->method,
            ), $args);
        } catch (\Exception $e)
        {
            $str = "\n" . $e->getMessage() . "\nfile:" . $e->getFile() . '>' . $e->getLine() . "\n";
            echo $str;
            if (method_exists($e, 'getDebugMsg'))
            {
                echo "\n";
            }

            if (method_exists($e, 'getInfos'))
            {
                echo "\n";
            }
            $str2 = $e->getTraceAsString();
            echo $str2;
            echo "\nDEBUG INFO\n";
            if (Sys::app()->interruption()->isThrower())
            {
                $str = "\n" . Sys::app()->interruption()->getDebugMsg() . "\n";
                var_export(Sys::app()->interruption()->getDebugData());
                echo "\n";
            }
            file_put_contents(self::getErrorLogFile(), date('Y-m-d H:i:s', time()) . "\t" . self::$cmd . "\n{$str}{$str2}\n", FILE_APPEND);
        }
    }


    function getArgs($args)
    {
        $opts = array(
            'script'  => '',
            'command' => '',
            'method'  => '',
            'args'    => array(),
        );
        if (isset($args[0]))
        {
            $opts['script'] = $args[0];
            if (isset($args[1]))
            {
                $ar = explode('/', $args[1]);
                array_unshift($ar, 'cli');
                $ar[count($ar) - 1] = 'Cmd' . ucfirst($ar[count($ar) - 1]);
                $opts['command']    = join('\\', $ar);
                if (isset($args[2]))
                {
                    $opts['method'] = $args[2];
                    $argsLength     = count($args);
                    if ($argsLength > 3)
                    {
                        for ($i = 3; $i < $argsLength; $i++)
                        {
                            $array = explode('=', $args[$i]);
                            if (count($array) == 2)
                                $opts['args'][str_replace('--', '', $array[0])] = $array[1];
                        }
                    }
                }
                else
                {
                    $opts['method'] = 'initCmd';
                }
            }
            else
            {
                self::errorMsg('unknown command name');
            }
        }
        else
        {
            self::errorMsg('script not exist?');
        }

        if (class_exists($opts['command']))
        {
            $methods = get_class_methods($opts['command']);
            if (in_array($opts['method'], $methods))
            {
                $this->command = $opts['command'];
                $this->method  = $opts['method'];
                $this->args    = $opts['args'];
            }
            else
            {
                self::errorMsg('METHOD NOT EXIST::' . $opts['method'] . ' not exist in ' . $opts['command'] . "\n\t methods:\n\t" . join("\n\t", $methods));
            }
        }
        else
        {
            self::errorMsg('CMD NOT EXIST:: command ' . $opts['command']);
        }
    }


    public static function errorMsg($msg)
    {
        throw new \Exception($msg, 400);
        //die;
    }

    function shutDown()
    {
        $d = error_get_last();
        if ($d)
        {
            echo "\n";
            var_dump($d);
            //debug_print_backtrace();

            file_put_contents(self::getErrorLogFile(), date('Y-m-d H:i:s', time()) . "\t" . self::$cmd . "\n" . var_export($d, true) . "\n", FILE_APPEND);
            echo "\n";
        }
    }

    public static function getErrorLogFile()
    {
        if (self::$__errorLogFile === '')
        {
            $date                 = date('Ymd');
            self::$__errorLogFile = Sys::app()->params['cli']['logDir'] . '/app.error_' . $date . '.log';
            if (!file_exists(self::$__errorLogFile))
            {
                $tmp_dir = dirname(self::$__errorLogFile);
                if (!file_exists($tmp_dir))
                {
                    mkdir($tmp_dir, 0777, true);
                }

                file_put_contents(self::$__errorLogFile, "\n", FILE_APPEND);
                chmod(self::$__errorLogFile, 0777);
            }

        }
        return self::$__errorLogFile;

    }

    public static function getLogFile()
    {
        if (self::$__logFile === '')
        {
            if (!file_exists(Sys::app()->params['cli']['logDir']))
            {
                mkdir(Sys::app()->params['cli']['logDir'], 0777, true);
            }
            $date            = date('Ymd');
            self::$__logFile = Sys::app()->params['cli']['logDir'] . '/app.history_' . $date . '.log';
            if (!file_exists(self::$__logFile))
            {
                file_put_contents(self::$__logFile, "\n", FILE_APPEND);
                usleep(200);
                if (posix_geteuid() === fileowner(self::$__logFile))
                {
                    @chmod(self::$__logFile, 0777);//wsl 下权限可能有问题
                }
            }
        }
        return self::$__logFile;

    }


}

