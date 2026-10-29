<?php
/*
use Workerman\Worker;
require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('http://0.0.0.0:8080');
$worker->onMessage = function($connection, $request) {
    $connection->send("Hello Workerman from WSL2!");
};

Worker::runAll();
*/

$ip     = '0.0.0.0';
$port   = 8005;
$cfgKey = 'kl-pc';


defined('__APP_DIR__') or define('__APP_DIR__', dirname(__DIR__));
defined('__HAMMER_DIR__') or define('__HAMMER_DIR__', dirname(__APP_DIR__));
defined('__HAMMER_DEBUG__') or define('__HAMMER_DEBUG__', true);

ini_set('display_errors', __HAMMER_DEBUG__ ? 'On' : 'Off');
ini_set('log_errors', 'On');
error_reporting(E_ALL);

// 只做进程级致命错误日志，不做响应输出
function lastError()
{
    $err = error_get_last();
    if (!$err)
        return;
    if (!in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true))
        return;
    try
    {
        \hammer\sys\Sys::app()->log('FATAL', $err);
    } catch (\Throwable $e)
    {
        error_log(sprintf('[FATAL] %s @ %s:%d', $err['message'], $err['file'], $err['line']));
    }
}

require __HAMMER_DIR__ . '/autoloader.php';
require __HAMMER_DIR__ . '/vendor/autoload.php';

$file = __APP_DIR__ . "/config/env/{$cfgKey}.php";
if (!is_file($file))
{
    die("config not found: {$file}\n");
}
\hammer\sys\Sys::init(require $file);

// ★ 关键：不要在这里 new HttpApp。
//   否则该类在 Master 进程就被 autoload 到内存，reload 出的新 Worker fork 自 Master，
//   用的还是旧类 → 你改了 HttpApp 或它引用的服务类也不会生效。
$httpAppTpl = null;

$worker            = new \Workerman\Worker("http://{$ip}:{$port}");
$worker->maxConnections = 10000; // 限制此 Worker 进程的最大连接数

$httpAppTpl        = (new \hammer\web\HttpApp())->setIsResOutput(false);
$worker->onMessage = function ($connection, $request) use (&$httpAppTpl)
{
    // ---- CORS 预检 ----
    if ($request->method() === 'OPTIONS')
    {
        $connection->send(new \Workerman\Protocols\Http\Response(204, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]));
        return;
    }

    // ---- 拼装 $_SERVER 兼容 map（Workerman 的 Request 没有 ->server 属性） ----
    $serverMap = [
        'REQUEST_METHOD' => $request->method(),
        'REQUEST_URI'    => $request->uri(),
        'QUERY_STRING'   => $request->queryString() ?? '',
        'REMOTE_ADDR'    => $connection->getRemoteIp(),
        'SERVER_PORT'    => $connection->getLocalPort(),
        'SERVER_NAME'    => $connection->getLocalIp(),
    ];

    $webReq = (new \hammer\web\WebRequest($serverMap))->setGetVar($request->get() ?? [])->setPostVar($request->post() ?? [])->setHeaderVar($request->header() ?? [])->setCookieVar($request->cookie() ?? [])->setFileVar($request->file() ?? [])       // Workerman 是 file()，不是 files()
        ->setRawBody($request->rawBody());           // Workerman 是 rawBody()，不是 rawContent()

    // ★ 懒加载：让类在 Worker 进程首次请求时才 autoload，
    //   保证 reload 后的新 Worker 能拿到最新代码（放在 master 里 new 会被 fork 复用旧类）。
    if ($httpAppTpl === null)
    {
        // 请按你框架实际类名替换
        $httpAppTpl = new \hammer\web\HttpApp();
    }

    $httpApp = clone $httpAppTpl;           // 每请求一份，杜绝状态串
    $httpApp->setWebRequest($webReq);

    try
    {
        $body = $httpApp->run();            // 若框架是 echo 型，改成 ob_start/ob_get_clean 捕获

        if (__HAMMER_DEBUG__ && !is_scalar($body) && $body !== null)
        {
            error_log('[workerman] non-scalar body: ' . var_export($body, true));
        }

        $connection->send(new \Workerman\Protocols\Http\Response($httpApp->getStatusCode() ?: 200, ['Content-Type' => $httpApp->getContentType() ?: 'text/html; charset=utf-8'], (string)$body));
    } catch (\Throwable $e)
    {
        \hammer\sys\Sys::app()->log('ERROR', [
            'msg'  => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        // Workerman 里 send 在连接已关闭时会返回 false，不抛异常，
        // 所以不需要 isWritable() 式的判断；直接 send 即可，失败会被静默丢弃。
        $connection->send(new \Workerman\Protocols\Http\Response(500, ['Content-Type' => 'application/json; charset=utf-8'], json_encode([
                'status' => 500,
                'code'   => 'internal_error',
                'msg'    => 'Internal Server Error',
                '_debug' => __HAMMER_DEBUG__ ? $e->getMessage() : null,
            ])));
    }
};
echo "\n#";
echo phpversion();
echo "\n#Workerman http server is running at {$ip}:{$port}\n";
\Workerman\Worker::$eventLoopClass = \Workerman\Events\Event::class;
//\Workerman\Worker::$maxConnections   = 10000; // 根据你的服务器配置和业务需求调整


\Workerman\Worker::runAll();

