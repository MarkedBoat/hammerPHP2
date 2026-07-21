<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;


class ActionMakeConvTasks extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();


        $sse = new SimpleSSE();

        $sse->send("\n调用命令，需要手动\n");
        $sse->send("\nmake conv tasks\n");

        $hammerDIr=__HAMMER_DIR__;
        $sse->simpleExecCmd("cd {$hammerDIr};./hammer  homepc/xx make_task  --env='kl-pc'");
        $sse->end();

        die;
        // $ar = scandir("/mnt/f/tmp2/tmp");

         \hammer\web\HttpApp::$hasOutput = true;
        //  die(file_get_contents("/mnt/f/tmp.txt"));

        $srcDir = '/mnt/f/tmp2/tmp/';
        $dstDir = '/mnt/f/tmp2/format/wait/php/';
        echo "\n调用命令，需要手动\n";
        echo "\nmake conv tasks\n";
        //  passthru("sudo /porter/app/app.php  homepc/xx make_task --env=kl-pc ", $res);
        //echo passthru("php /porter/app/app.php  homepc/xx make_task  --env='kl-pc'", $res);
        echo passthru("/app  homepc/xx make_task  --env='kl-pc'", $res);

        //die("\n\n\nsudo /app  homepc/xx make_task --env=dev0\n\n\nsudo /app  homepc/xx make_task --env=dev0 --force_renew=yes\n");

        die;
    }

}