<?php

namespace modules\dev\v1\api\KL_PC\music;

use hammer\sys\Sys;
use hammer\web\ActionBase;
use hammer\web\HttpApp;


class ActionGetLrc extends ActionBase
{

    //后期静态绑定代替了

    public function run()
    {
        HttpApp::$hasOutput = true;
        $name               = $this->inputBox->getNotEmptyString('songName');
        $file               = "/mnt/f/music/mp3/{$name}.lrc";
        if (file_exists($file))
        {
            die(file_get_contents($file));
        }
        else
        {
            die('error');
        }
    }

}