<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionRenameFileTitle extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $oldname = $this->inputBox->getNotEmptyString('oldStr');
        $newname = $this->inputBox->getNotEmptyString('newStr');
        $id      = $this->inputBox->getNotEmptyInt('id');

        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $taskM   = $taskM->findByPk($id);
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
        $video_path = "{$rootDir}{$albumDirPath}{$taskM->video_title}.{$taskM->video_ext}";


        if (file_exists($video_path))
        {
            // @unlink($video_path);
            rename($video_path, str_replace($oldname, $newname, $video_path));
            usleep(20000);
            $taskM->video_title = str_replace($oldname, $newname, $taskM->video_title);
        }
        else
        {

            $taskM->is_src_exist = Def::staNot;

        }
        $taskM->save();

        return ['input' => $this->inputBox->getData(), 'attrs' => $taskM->getAttributes(), 'videoFilename' => $video_path, 'src_exist' => file_exists($video_path)];
    }

}