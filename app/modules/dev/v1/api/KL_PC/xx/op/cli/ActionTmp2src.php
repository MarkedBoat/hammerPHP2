<?php

namespace modules\dev\v1\api\KL_PC\xx\op\cli;

use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionTmp2src extends ActionBase
{

    //后期静态绑定代替了

    public function run()
    {
        \hammer\sys\Sys::app()->setDebug(true);

        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        \hammer\web\HttpApp::$hasOutput = true;
        //  die(file_get_contents("/mnt/f/tmp.txt"));

        $srcDir = '/mnt/f/tmp2/tmp';
        $dstDir = '/mnt/f/tmp2/format/wait/src';
        echo "\n调用命令\n";
        echo "\nmv\n";
        // passthru("mv {$srcDir}*  {$dstDir}", $res);

        $hammer_dir = __HAMMER_DIR__;
        $sse        = new SimpleSSE();
        $sse->simpleExecCmd("cd {$hammer_dir}; ./hammer  homepc/xx/format tmp2src --env='kl-pc'  --src='{$srcDir}' --dst='{$dstDir}'  ");

        $sse->send("\n\n--------------------------------------------------\n");

        $sse->send("\nls -l --block-size=M {$dstDir}\n");
        $sse->simpleExecCmd("ls -lR  {$dstDir}");


        $sse->send("\n\n--------------------------------------------------\n");

        $sse->send("\nls -lR {$srcDir}\n");
        $sse->simpleExecCmd("ls -lR {$srcDir}");
        $sse->end();
        die;
    }

}