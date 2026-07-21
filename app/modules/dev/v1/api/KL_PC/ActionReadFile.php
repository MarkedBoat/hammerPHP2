<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionReadFile extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        $ar = scandir("/mnt/f/tmp2/tmp");
        //var_dump($ar);
        //$href = 'http://kl-home-pc:8001/mnt/f/tmp2/tmp/%20FC2-PPV-4049718%20%E5%B8%8C%E6%9C%9B%E7%94%B7%E5%8F%8B%E7%94%A8%E5%8A%9B%E6%93%8D%E5%A5%B9%E7%9A%84%E9%AA%9A%E8%B4%A7%E5%A5%B3%E4%BA%BA.mp4';

        //header("HTTP/1.1 302");
        //Header("Location: {$href}");
        //die('xxxx2');
        $list = [];
        foreach ($ar as $str)
        {
            if (strstr($str, '.mp4'))
            {
                //  echo "<a href='/mnt/f/tmp2/tmp/{$str}' target='_blank'>{$str}</a><br>";
                $list[] = ['title' => $str, 'src' => "/mnt/f/tmp2/tmp/{$str}"];
            }
        }
        // die;

        return ['list' => $list];
    }

}