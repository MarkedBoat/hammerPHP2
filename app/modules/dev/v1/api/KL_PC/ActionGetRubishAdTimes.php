<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionGetRubishAdTimes extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        $rubishAdTimesFilename = __APP_DIR__ . '/config/file/xx_video/rubish_ad_times.json';
        $rubishAdTimes         = [];
        if (!file_exists($rubishAdTimesFilename))
        {
            $rubishAdTimes = json_decode('[{"header":0,"tail":0},{"header":152,"tail":129},{"header":141,"tail":129},{"header":141,"tail":169},{"header":184,"tail":192},{"header":40,"tail":54},{"header":28,"tail":0},{"header":22,"tail":0},{"header":24,"tail":23},{"header":14,"tail":0},{"header":17,"tail":17},{"header":40,"tail":54}]', true);
        }
        else
        {
            $rubishAdTimes = array_map(function ($str)
            {
                $ar = explode(',', $str);
                return ['header' => intval($ar[0]), 'tail' => intval($ar[1])];
            }, explode("\n", file_get_contents($rubishAdTimesFilename)));
        }
        return ['rubishAdTimes' => $rubishAdTimes,'x'=>file_exists($rubishAdTimesFilename)];

         \hammer\web\HttpApp::$hasOutput = true;
        die(file_get_contents($filename));

    }

}