<?php

namespace modules\dev\v1\api\KL_PC\xx\op\cli;

use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;


class ActionFormatFileTitle extends ActionBase{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        $hammer_dir=__HAMMER_DIR__;
        $tmpDir='/mnt/f/tmp2/tmp';
        $sse = new SimpleSSE();
     //   $this->printer->tabEcho($markCmd);
        //$sse->simpleExecCmd("cd {$hammer_dir}; ./hammer homepc/xx/task mark  --env=kl-pc  --dir='{$tmpDir}';");

        $sse->simpleExecCmd("cd {$hammer_dir}; ./hammer  homepc/xx/format v1 --env='kl-pc'  --root_dir='{$tmpDir}' --preview=no --retry=yes1 ");
        $sse->end();
        die;

    }

}