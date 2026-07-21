<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionTmp2php extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        \hammer\sys\Sys::app()->setDebug(true);

        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

         \hammer\web\HttpApp::$hasOutput = true;
        //  die(file_get_contents("/mnt/f/tmp.txt"));

        $srcDir='/mnt/f/tmp2/tmp/';
        $dstDir='/mnt/f/tmp2/format/wait/php/';
        echo "\n调用命令\n";
        echo "\nmv\n";
        passthru("mv {$srcDir}*  {$dstDir}", $res);

        var_dump($res);
        echo "\nls -l --block-size=M {$dstDir}\n";
        passthru("ls -l {$dstDir}");
        echo "\nls -l {$srcDir}\n";
        passthru("ls -l {$srcDir}");

        die;
    }

}