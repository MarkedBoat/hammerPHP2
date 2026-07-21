<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

defined('__HAMMER_DIR__') or define('__HAMMER_DIR__', dirname(dirname(__DIR__)));
defined('__APP_DIR__') or define('__APP_DIR__', dirname(__DIR__));

defined('__HOST__') or define('__HOST__', $_SERVER['HTTP_HOST']);
defined('__HAMMER_DEBUG__') or define('__HAMMER_DEBUG__', true);
ini_set('display_errors', 'On');
error_reporting(11);

$host        = __HOST__;
$configFiles = [
    'kl-screenlock.fengmi.tv'   => 'test',
    'kl1-screenlock.fengmi.tv'  => 'test',
    'huxiaobao.fangtangtv.com'  => 'prod',
    'pad-hxb.fangtangtv.com'    => 'prod',
    'huxiaobao1.fangtangtv.com' => 'pre',
    'porter.bfcode.com'         => 'debug',
    //'porter.kl.com'             => 'dev0',
    'porter.dev.com'            => 'poseidon_test',
    'kl-home-pc:8001'           => 'kl-pc',
    'kl-home-pc:8002'           => 'kl-pc',
    'kl-home-pc:8003'           => 'kl-pc',
    '124.112.79.235:8002'           => 'kl-pc',

];


function lastError()
{
    if (\hammer\web\HttpApp::$hasOutput)
        return false;
    $err = error_get_last();
    if ($err)
    {
        @ob_end_clean();
        // if( \app\sys\\Sys::app()->params['errorHttpCode']===400){
        @header('HTTP/1.1 400 Not Found');
        @header("status: 400 Not Found");
        //}

        @header('content-Type:text/json;charset=utf8');
        $res = ['status' => 400, 'code' => 'code_error_', 'msg' => '服务器错误','_'=>\hammer\sys\Sys::app()->isDebug()];
        if (1 || \hammer\sys\Sys::app()->isDebug())
        {
            $err['message'] = explode("\n", $err['message']);
            $res['__debug'] = [
                'out'   => __CLASS__ . '==>' . __METHOD__ . '() ##' . __LINE__,
                'log'   => \hammer\sys\Sys::app()->getLogs(),
                'error' => $err
            ];
        }
        echo json_encode($res);
    }
    else
    {
        //do nothing
    }
}

register_shutdown_function('lastError');
require '../../autoloader.php';
require '../../vendor/autoload.php';

if (isset($configFiles[__HOST__]))
{
    $file = __APP_DIR__ . "/config/env/{$configFiles[__HOST__]}.php";
    if (is_file($file) && is_readable($file))
    {
        $config = require $file;
        \hammer\sys\Sys::init($config);
    }
    else
    {
        die("not exist:{$file}");
    }
}
else
{
    die("domain not in config array: " . __HOST__);
}

(new \hammer\web\HttpApp())->run();


