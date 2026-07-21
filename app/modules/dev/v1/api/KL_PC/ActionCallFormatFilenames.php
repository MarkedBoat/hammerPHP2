<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;


class ActionCallFormatFilenames extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        $hammer_dir=__HAMMER_DIR__;
        $sse = new SimpleSSE();
        $sse->simpleExecCmd("cd {$hammer_dir}; ./hammer  homepc/xx formatName2 --env='kl-pc'  --root_dir='/mnt/f/tmp2/tmp' --preview=no --retry=yes1 ");
        $sse->end();
        die;
         \hammer\web\HttpApp::$hasOutput = true;
        echo "\n调用命令 重命名\n";
        // passthru("php /mnt/f/doc/bfcode/porter/app/app.php  homepc/file moveFiles --env=dev0  --src='/mnt/f/download2' --dst='/mnt/f/tmp2/tmp' --csv='/mnt/f/tmp.txt' ",$res);
        passthru("/app  homepc/xx formatName2 --env='kl-pc'  --root_dir='/mnt/f/tmp2/tmp' --preview=no --retry=yes1 ", $res);


        var_dump($res);
        die;
    }

}