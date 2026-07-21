<?php

namespace models\ext\tool;

//use common\models\tool\SysAdv;
use hammer\sys\Sys;

class SimpleSSE
{
    private bool $isClose = false;

    public function __construct($auto = true)
    {
        if ($auto)
        {
            $this->header()->start();
        }
    }

    public function header(): static
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        return $this;
    }

    public function start(): static
    {
        // 发送开始事件
        echo "event: start\n";
        echo "data: 开始执行:sse\n\n";
        ob_flush();
        flush();
        return $this;
    }

    public function send($msg): static
    {
        echo "event: output\n";
        echo "data: " . str_replace("\n", "<br>", $msg) . "\n\n";

        ob_flush();
        flush();
        return $this;
    }

    public function end(): static
    {
        $this->isClose = true;
        // 发送完成事件
        echo "event: complete\n";
        echo "data: 命令执行完成\n\n";
        ob_flush();
        flush();
        return $this;
    }

    public function simpleExecCmd($command): bool
    {
        $process = popen($command . ' 2>&1', 'r');

        while (!feof($process))
        {
            $output = fgets($process);
            if ($output)
            {
                $this->send($output);
            }
            usleep(1000);
        }

        pclose($process);
        return true;
    }

    public function __destruct()
    {
        if ($this->isClose === false)
        {
            $this->end();
        }
    }

}