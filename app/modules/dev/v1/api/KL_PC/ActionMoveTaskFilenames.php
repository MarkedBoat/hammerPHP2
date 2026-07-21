<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionMoveTaskFilenames extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
       // $ar = scandir("/mnt/f/tmp2/tmp");

         \hammer\web\HttpApp::$hasOutput =true;
      //  die(file_get_contents("/mnt/f/tmp.txt"));
        echo file_get_contents("/mnt/f/tmp.txt");
        echo "\n调用命令\n";
        passthru('ls -l /porter/app/app');
        $cmd="/app homepc/file moveFiles --env='kl-pc'  --src='/mnt/f/download2' --dst='/mnt/f/tmp2/tmp' --csv='/mnt/f/tmp.txt' ";

        passthru($cmd,$res);

        var_dump($res);

        echo "\n{$cmd}\n";
        die;
    }

}