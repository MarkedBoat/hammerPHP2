<?php


namespace cli\dev\cmd;

use hammer\cli\CmdBase;
use hammer\sys\Sys;

ini_set('memory_limit', '2048M');


class CmdRoot extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    // /hammer_porter system/queue call --env=dev0 queue=root_cmd_queue class=\cli\dev\cmd\CmdRoot --method=cmd_web_input --timeLimit=200

    public function cmd_web_input($rst) {
        //Sys::app()->redis()->lPush(Sys::app()->params['cli']['root_cmd_queue'], json_encode([$date, $cmds[$cmd]]));

        //    $param    = new DataBox(json_decode($rst, true));
        $ar       = json_decode($rst,true);
        var_dump($ar);
        exec($ar[1],$ar);
        var_dump($ar);
        return 'ok';
    }

    public function out($str, $level = 2) {
        echo str_repeat(' ', $level * 10) . $str . "\n";
    }



}