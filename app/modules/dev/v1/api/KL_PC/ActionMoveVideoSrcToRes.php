<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\common\Def;
use hammer\sys\Sys;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionMoveVideoSrcToRes extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        Sys::app()->setDebug(true);
        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));


        $srcVideoInfo = $taskM->getSrcVideoInfo($rootDir);
        $dstVideoInfo = $taskM->getDstVideoInfoForce($rootDir);

        if ($srcVideoInfo['filesize'] === 0)
        {
            $this->setMsg('src 文件不存在')->setCode('src_video_not_exist')->outError();
        }
        if ($dstVideoInfo['filesize'] > 0)
        {
            $this->setMsg('dst 文件已经存在')->setDebugData($dstVideoInfo)->setCode('res_video_has_exist')->outError();
        }

        $srcVideoFilename = $srcVideoInfo['filename'];
        $dstVideoFilename = $dstVideoInfo['dstFilename'];
        $dstVideoDir      = dirname($dstVideoFilename);


        if (!file_exists($dstVideoDir))
        {
            mkdir($dstVideoDir, 0777, true);
        }
        rename($srcVideoFilename, $dstVideoFilename);

        $srcVideoInfo = $taskM->getSrcVideoInfo($rootDir);
        $dstVideoInfo = $taskM->getDstVideoInfoForce($rootDir);


        //  $taskM->save();

        return ['input' => $this->inputBox->getData(), 'task' => $taskM->getAttributes(), 'dst_exist' => file_exists($dstVideoFilename), 'src_exist' => file_exists($srcVideoFilename), '$dstVideoInfo' => $dstVideoInfo, '$srcVideoInfo' => $srcVideoInfo];
    }

}