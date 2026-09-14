<?php

namespace modules\dev\v1\api\KL_PC\xx\op;

use hammer\web\ActionBase;


class ActionSaveMoveTaskFilenames extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        $hammer_dir=__HAMMER_DIR__;
        $command = "    
        cd {$hammer_dir};
        ./hammer homepc/file moveFiles --env='kl-pc'  --src='/mnt/f/download2' --dst='/mnt/f/tmp2/tmp' --csv='/mnt/f/tmp.txt';
      ";
        // 发送开始事件
        echo "event: start\n";
        echo "data: 开始执行: $command\n\n";
        ob_flush();
        flush();


        echo "event: output\n";
        echo "data: " . str_replace("\n", "", '保存') . "\n\n";

        $text = $this->inputBox->getNotEmptyString('text');
        file_put_contents("/mnt/f/tmp.txt", $text);

        echo "event: output\n";
        echo "data: " . str_replace("\n", "<br>", file_get_contents("/mnt/f/tmp.txt")) . "\n\n";

        ob_flush();
        flush();


        echo "event: output\n";
        echo "data: " . str_replace("\n", "<br>", 'ls -l /porter/app/app<br>' . passthru('ls -l /porter/app/app')) . "\n\n";

        ob_flush();
        flush();


        echo "event: output\n";
        echo "data: " . str_replace("\n", "<br>", $command) . "\n\n";

        ob_flush();
        flush();

        if (1)
        {
            // 执行命令
            $process = popen($command . ' 2>&1', 'r');

            while (!feof($process))
            {
                $output = fgets($process);
                if ($output)
                {
                    echo "event: output\n";
                    echo "data: " . str_replace("\n", "", $output) . "\n\n";
                    ob_flush();
                    flush();
                }
                usleep(1000);
            }

            pclose($process);
        }
        else
        {
            for ($i = 0; $i < 10; $i++)
            {
                echo "event: output\n";
                echo "data: " . str_replace("\n", "", date('Y-m-d H:i:s')) . "\n\n";
                ob_flush();
                flush();
                sleep(1);
            }
        }

        // 发送完成事件
        echo "event: complete\n";
        echo "data: 命令执行完成\n\n";
        ob_flush();
        flush();

        exit;


        die;
        $text = $this->inputBox->getNotEmptyString('text');
        file_put_contents("/mnt/f/tmp.txt", $text);
         \hammer\web\HttpApp::$hasOutput = true;
        die(file_get_contents("/mnt/f/tmp.txt"));
    }

}