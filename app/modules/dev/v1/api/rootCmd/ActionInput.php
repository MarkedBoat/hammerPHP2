<?php

namespace modules\dev\v1\api\rootCmd;

use hammer\web\ActionBase;
use hammer\sys\Sys;


class ActionInput extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $cmds = [
            'mysql_env_slave' => 'cat /data/code/debug/poseidon.conf_mysql.slave.php > /data/code/debug/poseidon.conf_mysql',
            'mysql_env_dev'   => 'cat /data/code/debug/poseidon.conf_mysql.dev.php > /data/code/debug/poseidon.conf_mysql',
        ];

        $cmd  = $this->inputBox->getNotEmptyString('cmd');
        $date = $this->inputBox->getNotEmptyString('date');
        if (!isset($cmds[$cmd]))
            $this->setMsg('cmd error')->setCode('cmd_error')->outError();
        Sys::app()->redis()->lPush(Sys::app()->params['cli']['root_cmd_queue'], json_encode([$date, $cmds[$cmd]]));
        return Sys::app()->redis()->lGetRange(Sys::app()->params['cli']['root_cmd_queue'], 0, 200);

    }

}