<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionMoveAsUnformatVideo extends ActionBase
{
    //后期静态绑定代替了

    public function run()
    {

        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));


        $srcVideoInfo = $taskM->getSrcVideoInfo($rootDir);
        $dstVideoInfo = $taskM->getDstVideoInfo($rootDir);

        $relativeResFilename = $this->dst_video_info['src'] ?? '';
        $srcVideoFilename    = $srcVideoInfo['filename'];
        $dstVideoFilename    = $dstVideoInfo['filename'];
        $newSrcVideoFilename = str_replace('/FFOutput_res/', '/', $dstVideoFilename);
        $newSrcVideoDir      = dirname($newSrcVideoFilename);

        $errors = [];
        $logs   = [
            'old_src' => "{$srcVideoFilename}",
            'old_std' => "{$dstVideoFilename}",
            'new_src' => "{$newSrcVideoFilename}",
        ];
        //这个dst是现在的 dst
        if ($dstVideoInfo['filesize'] > 0)
        {
            //  unset($dstVideoInfo['filename']);
            //@unlink($srcVideoInfo['filename']);
            if (!file_exists($newSrcVideoDir))
            {
                mkdir($newSrcVideoDir, 0777, true);
            }
            else
            {
                $logs[] = "目标视频文件目录已存在  {$newSrcVideoDir}";
            }
            if ($dstVideoFilename === $newSrcVideoFilename)
            {
                if (file_exists($newSrcVideoFilename))
                {
                    $logs[] = "old std 就是 new  src 且存在:  {$newSrcVideoFilename}";
                    @unlink($srcVideoFilename);

                }
                else
                {
                    $errors[] = "old std 就是 new  src 但不存在 {$newSrcVideoFilename}";
                }
            }
            else
            {
                rename($dstVideoFilename, $newSrcVideoFilename);
                if (file_exists($newSrcVideoFilename))
                {
                    $logs[] = "rename成功 new src:  {$newSrcVideoFilename}";
                    if (file_exists($dstVideoFilename))
                    {
                        $errors[] = "rename之后,就文件还存在 {$dstVideoFilename}";
                    }
                    else
                    {
                        @unlink($srcVideoFilename);
                    }
                }
                else
                {
                    $errors[] = "rename之后,目标视频文件[new src video]不存在 {$newSrcVideoFilename}";
                }
            }


        }
        else
        {
            $errors[] = "目标视频文件不存在";
        }


        //  $taskM->save();

        return ['logs' => $logs, 'errors' => $errors, 'input' => $this->inputBox->getCoreArray(), 'task' => $taskM->getAttributes(), 'old_dstVideoInfo' => $dstVideoInfo, 'old_srcVideoInfo' => $srcVideoInfo];
    }

}