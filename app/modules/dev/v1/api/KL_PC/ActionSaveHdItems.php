<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\sys\Sys;
use hammer\web\ActionBase;
use hammer\web\HttpApp;


class ActionSaveHdItems extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        Sys::app()->setDebug(true);
        ini_set('display_errors', 1);
        error_reporting(11);
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        $hdItemsFilename = __APP_DIR__ . '/config/file/xx_video/hd_items.json';
        $dir             = dirname($hdItemsFilename);
        if (!file_exists($dir))
        {
            mkdir($dir, 0777, true);
        }

        $json    = $this->inputBox->getNotEmptyString('json');
        $inputAr = json_decode($json, true);
        if (empty($inputAr))
        {
            throw new \Exception('空json');
        }

        $heights  = array_column($inputAr, 'height');
        $maxRateK = array_column($inputAr, 'maxRateK');
        $fps      = array_column($inputAr, 'fps');

        array_multisort($heights, SORT_NUMERIC, SORT_ASC, $maxRateK, SORT_NUMERIC, SORT_ASC, $fps, SORT_NUMERIC, SORT_ASC, $inputAr);

        file_put_contents($hdItemsFilename, json_encode($inputAr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        HttpApp::$hasOutput = true;
        var_dump($inputAr);
        die(file_get_contents($hdItemsFilename));
    }

}