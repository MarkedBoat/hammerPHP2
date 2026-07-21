<?php

namespace modules\dev\v1\api\rootCmd;

use hammer\web\ActionBase;


class ActionReadFile extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $file = $this->inputBox->getNotEmptyString('file');
        if (!is_file($file) || !is_writeable($file) || !strstr($file, 'debug'))
            $this->setCode('file_error')->setMsg('file_error')->outError();
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data))
            $this->setCode('json_error')->setMsg('json错误')->outError();
        return $data;
    }

}