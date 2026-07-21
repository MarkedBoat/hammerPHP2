<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionSaveRubishAdTimes extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        // $ar = scandir("/mnt/f/tmp2/tmp");

        $filename = __APP_DIR__ . '/config/file/xx_video/rubish_ad_times.json';
        $dir      = dirname($filename);
        if (!file_exists($dir))
        {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($filename))
        {
            $defaultAr = array_map(function ($row) { return "{$row['header']},{$row['tail']}"; }, json_decode('[{"header":0,"tail":0},{"header":152,"tail":129},{"header":141,"tail":129},{"header":141,"tail":169},{"header":184,"tail":192},{"header":40,"tail":54},{"header":28,"tail":0},{"header":22,"tail":0},{"header":24,"tail":23},{"header":14,"tail":0},{"header":17,"tail":17},{"header":40,"tail":54}]', true));

            file_put_contents($filename, join("\n", $defaultAr));
        }
        $defaultAr = explode("\n", file_get_contents($filename));

        $pattern     = '/[^\d,]/';
        $replacement = ''; // 替换为空字符串


        $inputAr = array_unique(array_filter(array_map(function ($str) { return preg_replace('/[^\d,]/', '', trim($str, " \n\r\t\v\0\,")); }, explode("\n", trim($this->inputBox->getNotEmptyString('text')))), function ($str) { return strlen($str) > 0; }));
        $newAr   = array_unique(array_merge($inputAr, $defaultAr));
        rsort($newAr, SORT_NUMERIC);

        file_put_contents($filename, join("\n", $newAr));
         \hammer\web\HttpApp::$hasOutput = true;
        var_dump($inputAr);
        die(file_get_contents($filename));
    }

}