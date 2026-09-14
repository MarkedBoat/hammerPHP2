<?php

namespace modules\dev\v1\api\KL_PC\music;

use hammer\web\ActionBase;


class ActionList extends ActionBase
{

    //后期静态绑定代替了

    public function run()
    {

        $cmd = 'ls /mnt/f/music/mp3/|grep mp3';
        exec($cmd, $ar);
        return array_map(function ($v)
        {
            $name = str_replace('.mp3', '', $v);
            return ['songName' => $name, 'urlStr' => ($name)];
        }, $ar);


    }

}