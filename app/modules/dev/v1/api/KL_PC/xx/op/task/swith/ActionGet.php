<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task\swith;

use hammer\web\ActionBase;


class ActionGet extends ActionBase
{

    //后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        $file = __APP_DIR__ . '/config/file/xx_video/php_task_state.txt';
        $dir  = dirname($file);
        if (!file_exists($dir))
        {
            mkdir($dir, 0777, true);
        }
        \hammer\web\HttpApp::$hasOutput = true;
        die(file_get_contents($file));
    }


}