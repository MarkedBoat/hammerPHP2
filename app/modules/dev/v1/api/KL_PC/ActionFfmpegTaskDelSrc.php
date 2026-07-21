<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionFfmpegTaskDelSrc extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        \hammer\sys\Sys::app()->setDebug(true);

        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $taskM   = $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));
        if ($taskM === false)
        {
            $this->setMsg('找不到任务')->outError();
        }

        if (strlen($taskM->album_dir) > 0)
        {
            $albumDirPath = "/{$taskM->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }
        $video_path = "{$rootDir}{$albumDirPath}{$taskM->video_basename}.{$taskM->video_ext}";


        if (file_exists($video_path))
        {
            @unlink($video_path);
            usleep(20000);
        }
        if (!file_exists($video_path))
        {
            $taskM->is_src_exist = Def::staNot;
            $taskM->save();
        }


        return ['input' => $this->inputBox->getCoreArray(), 'attrs' => $taskM->getAttributes(), 'videoFilename' => $video_path, 'src_exist' => file_exists($video_path)];
    }

}