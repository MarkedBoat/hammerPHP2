<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionGetRubishWords extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {
        @ob_clean();
        $rubishWordsFilename = __APP_DIR__ . '/config/file/xx_video/rubish_words.txt';
        $rubishWords         = [];
        if (file_exists($rubishWordsFilename))
        {
            $rubishWords = explode("\n", file_get_contents($rubishWordsFilename));
        }
        return ['rubishWords' => $rubishWords, 'x' => file_exists($rubishWordsFilename)];


    }

}