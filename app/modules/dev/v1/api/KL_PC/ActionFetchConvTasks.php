<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionFetchConvTasks extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

         \hammer\web\HttpApp::$hasOutput = true;
        //  die(file_get_contents("/mnt/f/tmp.txt"));

        $srcDir = '/mnt/f/tmp2/tmp/';
        $dstDir = '/mnt/f/tmp2/format/wait/php/';
        echo "\n调用命令,需要手动\n";
        echo "\nmake fetch tasks\n";
        //  passthru("sudo /porter/app/app.php  homepc/xx make_task --env=kl-pc ", $res);
        //echo passthru("php /porter/app/app.php  homepc/xx make_task  --env='kl-pc'", $res);
        die("\nsudo /app  homepc/xx fetchTasks --env=dev0\n");

        die;
    }

}