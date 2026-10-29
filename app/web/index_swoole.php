<?php
$ip     = '0.0.0.0';
$port   = 8004;
$cfgKey = $argv[1] ?? null;
if (!$cfgKey)
{
    fwrite(STDERR, "Usage: php {$argv[0]} <env>\n");
    exit(1);
}

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

$httpServer = new \Swoole\Http\Server($ip, $port);
$httpServer->set([
    'worker_num'       => 4,
    'max_request'      => 10000,
    'enable_coroutine' => false,

    // ★ 调试期关掉自动退出，避免和 reload 抢时机；生产建议设 5000~10000 防内存泄漏
    'max_request'      => 0,

    // ★ 平滑重启开关：先起新 Worker 接新请求，旧 Worker 处理完存量再退
    'reload_async'     => true,
    // ★ 旧 Worker 优雅退出的兜底秒数；必须 > 你最长单次请求耗时。
    //   有长任务（导出/大文件/队列消费）记得调大到 120~300
    'max_wait_time'    => 30,

    // ★ 运维便利：方便 kill -USR1 $(cat pid)
    'pid_file'         => __APP_DIR__ . "/../runtimes/swoole-{$cfgKey}.pid",
    'log_file'         => __APP_DIR__ . "/../runtimes/swoole-{$cfgKey}.log",
    'daemonize'        => false,   // 调试期前台跑，方便看日志
]);

$httpServer->on('WorkerStart', function ($server, $workerId) use (&$httpAppTpl)
{
    // ★ 调试期清缓存：OPcache + stat，保证 reload 后能读到新文件
    if (__HAMMER_DEBUG__)
    {
        if (function_exists('opcache_reset'))
            @opcache_reset();
        clearstatcache(true);
    }

    // ★ 在 Worker 里才第一次触达 HttpApp，
    //   composer autoload 是 lazy 的，Master 没提前加载 → 新 Worker 能拿到最新代码
    $httpAppTpl = (new \hammer\web\HttpApp())->setIsResOutput(false);

    register_shutdown_function('lastError');   // 每个 worker 注册一次，不累积
});

// ★ reload / shutdown 时会先停新连接、再回调这里，适合做资源清理
$httpServer->on('WorkerStop', function ($server, $workerId)
{
    error_log("[swoole] worker {$workerId} stopped gracefully");
});

$httpServer->on('Request', function ($request, $response) use (&$httpAppTpl)
{
    // CORS 预检
    if (($request->server['request_method'] ?? 'GET') === 'OPTIONS')
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->status(204);
        $response->end();
        return;
    }

    $s         = $request->server ?? [];
    $serverMap = [
        'REQUEST_METHOD' => $s['request_method'] ?? 'GET',
        'REQUEST_URI'    => $s['request_uri'] ?? '/',
        'QUERY_STRING'   => $s['query_string'] ?? '',
        'REMOTE_ADDR'    => $s['remote_addr'] ?? '',
        'SERVER_PORT'    => $s['server_port'] ?? '',
        'SERVER_NAME'    => $s['server_name'] ?? '',
    ];

    $webReq = (new \hammer\web\WebRequest($serverMap))->setGetVar($request->get ?? [])->setPostVar($request->post ?? [])->setHeaderVar($request->header ?? [])->setCookieVar($request->cookie ?? [])->setFileVar($request->files ?? [])->setRawBody($request->rawContent());

    $httpApp = clone $httpAppTpl;          // 每请求一份，杜绝状态串
    $httpApp->setWebRequest($webReq);

    try
    {
        $body = $httpApp->run();           // 若框架是 echo 型，改成捕获 ob

        // ★ 原 var_dump 会污染响应体，调试信息改走日志
        if (__HAMMER_DEBUG__ && !is_scalar($body) && $body !== null)
        {
            error_log('[swoole] non-scalar body: ' . var_export($body, true));
        }

        $response->header('Content-Type', $httpApp->getContentType() ?: 'text/html; charset=utf-8');
        $response->status($httpApp->getStatusCode() ?: 200);
        $response->end((string)$body);
    } catch (\Throwable $e)
    {
        \hammer\sys\Sys::app()->log('ERROR', ['msg' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        if (!$response->isWritable())
            return;
        $response->status(500);
        $response->header('Content-Type', 'application/json; charset=utf-8');
        $response->end(json_encode([
            'status' => 500,
            'code'   => 'internal_error',
            'msg'    => 'Internal Server Error',
            '_debug' => __HAMMER_DEBUG__ ? $e->getMessage() : null,
        ]));
    }
});

// ★ Master 退出时触发（比如 SIGTERM 关服务）
$httpServer->on('Shutdown', function ($server)
{
    error_log('[swoole] master shutdown');
});
echo "\n#";
echo phpversion();
echo "#Swoole http server is running at {$ip}:{$port}\n";
$httpServer->start();