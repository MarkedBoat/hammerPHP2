<?php

namespace modules\dev\v1\api\funtv\conf\file;

use hammer\web\ActionBase;


class ActionCover extends ActionBase
{
//后期静态绑定代替了

    //find /data/code/poseidon_server/ -name mysql_conf.php > /home/kinglone/poseidon_server_mysql_conf_files
    // find poseidon_server  -name index.php |xargs -I {} cp --path {} /data/code/conf/debug/  复制并创建不存在的目录
    public function run()
    {
        $file   = $this->inputBox->getNotEmptyString('file');
        $action = $this->inputBox->getNotEmptyString('action');
        $file   = '/data/code/' . $file;
        $files  = [
            'to'  => $file,
            'src' => str_replace('.php', ".{$action}.php", $file),
        ];
        foreach ($files as $type => $file)
        {
            if (!is_file($file))
            {
                $this->setMsg($file . '文件不存在')->outError();
            }
            if ($type === 'to' && !is_writeable($file))
            {
                $this->setMsg($file . '文件不可写')->setDebugData($_SERVER)->outError();
            }
        }
        return array('files' => $files, 'action' => $action, 'r' => file_put_contents($files['to'], file_get_contents($files['src'])));
    }

}