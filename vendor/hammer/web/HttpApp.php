<?php

namespace hammer\web;

use hammer\ActionBase;
use hammer\common\BaseApp;
use hammer\sys\Sys;


class HttpApp extends BaseApp
{

    /**
     * @var ActionBase
     */
    private       $__action  = null;
    public static $hasOutput = false;

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function run()
    {
        Sys::app()->setDispatcher($this);
        $uri             = trim(preg_replace('/\?(.*)?$/', '', $_SERVER['REQUEST_URI']), '/');
        $arr             = explode('/', $uri);
        $version         = $arr[1];
        $arr[1]          = explode('.', $version)[0];
        $arr[1]          .= '\\api';
        $lastIndex       = count($arr) - 1;
        $action          = $arr[$lastIndex];
        $arr[$lastIndex] = 'Action' . ucfirst($action);
        array_unshift($arr, 'modules');
        $actionClassPath = join('\\', $arr);
        // var_dump($actionClassPath);die;
        // Sys::app()->setDebug(true || isset($_REQUEST['kldebug']) && $_REQUEST['kldebug'] === 'x');
        Sys::app()->cache = isset($_REQUEST['nocache']) ? false : true;
        try
        {
            $this->initAction($actionClassPath);
            $this->__action->version = floatval(substr($version, 1));
            $this->output();
        } catch (\Exception $e)
        {
            $this->outError($e);
        }
    }


    public function initAction($actionClassPath)
    {
        if (class_exists($actionClassPath))
        {
            $this->__action = new $actionClassPath();
            $this->__action->baseInit();
        }
        else
        {
            Sys::app()->interruption()->setMsg('method不存在' . $actionClassPath)->outError();
            $this->expectError('server_error', 'method不存在', [], ['class not exist', $actionClassPath]);
        }
    }


    public function output()
    {
        try
        {
            $data = $this->__action->run();
            @ob_end_clean();
            @header('content-Type:application/json;charset=utf8');
            $data = [
                'status' => 200,
                'data'   => $data,
                'code'   => Sys::app()->interruption()->getCode(),
            ];
            if ($this->__action->isDebug())
            {
                $data['__debugs'] = [
                    'out'   => __CLASS__ . '==>' . __METHOD__ . '() ##' . __LINE__,
                    'log'   => Sys::app()->interruption()->getLogs(),
                    'error' => error_get_last()
                ];
            }
            $json            = json_encode($data, JSON_UNESCAPED_SLASHES);
            self::$hasOutput = true;
            die($json);

        } catch (\Exception $exception)
        {
            $this->outError($exception);
        }
    }


    public function expectError($code, $msg, $data, $debugData = [])
    {
        @ob_end_clean();
        //      @header('HTTP/1.1 200 Not Found');
        //      @header("status: 200 Not Found");
        if (Sys::app()->params['errorHttpCode'] === 400)
        {
            @header('HTTP/1.1 400 Not Found');
            @header("status: 400 Not Found");
        }
        @header('content-Type:application/json;charset=utf8');
        $data = [
            'status' => 400,
            'code'   => $code,
            'msg'    => $msg,
            'data'   => $data,

            //'server'     => ['nowTimestamp' => time()],
            '_'      => Sys::app()->isDebug(),
            '__'     => 'expectError',
        ];
        if (Sys::app()->isDebug())
        {
            $data['__debug'] = $debugData;
        }
        $json            = json_encode($data, JSON_UNESCAPED_SLASHES);
        self::$hasOutput = true;
        die($json);
    }

    public function outError(\Exception $exception)
    {
        if (Sys::app()->isDebug() || (!is_null($this->__action) && $this->__action->isDebug()))
        {

            $this->expectError('server_error', $exception->getMessage(), [], [
                'out'   => __CLASS__ . '==>' . __METHOD__ . '() ##' . __LINE__,
                'file'  => $exception->getFile() . '#' . $exception->getLine(),
                'log'   => Sys::app()->getLogs(),
                'trace' => explode("\n", $exception->getTraceAsString()),
                'error' => error_get_last()
            ]);
        }
        else
        {
            $this->expectError('server_error', $exception->getMessage(), []);
        }
    }


    public static function lastError($msg, $code, $lastError)
    {
        $keys = ['Allowed memory size', 'Invalid UTF-8 sequence in argument'];
        $log  = false;
        foreach ($keys as $kw)
            if (strstr($lastError['message'], $kw))
            {
                $log = true;
                break;
            }
        if ($log)
        {
            try
            {/*
                    Sys::redis('mpr')->lPush('mprErrorLog', date('Y-m-d H:i:s') . '###' . json_encode([
                            'isDebuging' => ITFC_DEBUG,
                            'status'     => 400,
                            'msg'        => $msg,
                            'file'       => $lastError['file'] . $lastError['line'],
                            'code'       => $code,
                            'debugMsg'   => $lastError['message'],
                            '__arg'      => Manger::$requstArgs,
                            '__debugs'   => Manger::getDebugInfos(),
                        ]));*/
                $data['__debugs'][] = ['title' => '记录错误', 'data' => 'ok'];
            } catch (\Exception $e)
            {
                if (isset($data['__debugs']))
                    $data['__debugs'][] = ['title' => '记录错误失败', 'data' => $e->getMessage()];
            }
        }

    }


    function gf_ajax_error($msg, $code = -1, $debug = [], $okCode = 'error')
    {
        $this->outJson([], $code, $msg, $debug, $okCode);
    }

    function gf_ajax_success($data, $extra = [], $okCode = 'ok')
    {
        $this->outJson($data, 0, '', $extra, $okCode);
    }


    public function outJson($data, $code, $msg, $extra = [], $okCode = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        if (Sys::app()->isDebug())
        {
            $extra['sys_debug'] = Sys::app()->interruption()->getLogs();
        }
        return exit(json_encode(array_merge(array(
            'code'    => $code,
            'msg'     => $msg,
            'bizRes'  => $okCode,
            'success' => $okCode === 'ok',
            'data'    => $data,
        ), $extra), JSON_UNESCAPED_UNICODE));
    }
}