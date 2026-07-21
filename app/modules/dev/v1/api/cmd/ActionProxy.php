<?php

namespace modules\dev\v1\api\cmd;

use hammer\web\ActionBase;


class ActionProxy extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        if (!isset($_FILES['file']))
            $this->setMsg('没有文件')->setDebugData($_FILES)->outError();
        $len      = count($_FILES['file']['name']);
        $dir      = $this->inputBox->tryGetString('dir');
        $rename   = $this->inputBox->tryGetString('rename');
        $baseName = 'base_name';
        if ($rename)
            $baseName = $this->inputBox->getNotEmptyString('baseName');
        $root_dir = '/data/upload';

        if ($dir)
        {
            $ar = explode('/', $dir);
            foreach ($ar as $str)
            {
                $str = trim($str);
                if (empty($str))
                {
                    continue;
                }
                $root_dir .= "/{$str}";
                if (!file_exists($root_dir))
                    mkdir($root_dir);
            }
        }
        $data = [__APP_DIR__, $root_dir];

        for ($i = 0; $i < $len; $i++)
        {
            $ar  = explode('.', $_FILES['file']['name'][$i]);
            $ext = $ar[count($ar) - 1];
            if ($rename === 'single' && $i === 0)
            {
                $fileName = "{$root_dir}/{$baseName}.{$ext}";
            }
            else if ($rename === 'all')
            {
                $fileName = "{$root_dir}/{$baseName}_{$i}.{$ext}";
            }
            else
            {
                $fileName = $root_dir . '/' . $_FILES['file']['name'][$i];

            }
            $r      = move_uploaded_file($_FILES['file']['tmp_name'][$i], $fileName);
            $data[] = [$fileName, $r];
        }
        return $data;


    }
    public function isDebug()
    {
        return true;
    }
}