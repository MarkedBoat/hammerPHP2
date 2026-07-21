<?php

namespace modules\dev\v1\api\rootCmd;

use hammer\web\ActionBase;


class ActionWriteFile extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $file = $this->inputBox->getNotEmptyString('file');
        $json = $this->inputBox->getNotEmptyString('json');
        $data = json_decode($json, true);
        if (!is_array($data))
            $this->setCode('json_error')->setMsg('json错误')->outError();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_file($file) || !is_writeable($file) || !strstr($file, 'debug'))
            $this->setCode('file_error')->setMsg('file_error')->outError();
        return [file_put_contents($file, $json),file_get_contents($file, $json)];
    }

}