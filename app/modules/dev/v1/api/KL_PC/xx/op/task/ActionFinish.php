<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionFinish extends ActionBase
{
    //后期静态绑定代替了

    public function run()
    {
        \hammer\sys\Sys::app()->setDebug(true);


        $rootDir = "/mnt/f/tmp2/format/wait";


        $srcRootDir = "/mnt/f/tmp2/format/wait/src";
        $isRubish   = $this->inputBox->tryGetString('isRubish') === 'yes';

        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();
        $taskM = $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));
        if ($taskM === false)
        {
            $this->setMsg('找不到任务')->outError();
        }
        $taskM->getLsCmdFileinfos($rootDir);

        if ($taskM->srcLsCmdFileinfo->filesize < 100000)
        {
            $this->setMsg('找不到原始文件')->outError();
        }
        if ($taskM->dstLsCmdFileinfo->filesize < 100000)
        {
            $this->setMsg('找不到转码后的文件')->outError();
        }
        //        var_dump($taskM->srcLsCmdFileinfo, $taskM->dstLsCmdFileinfo);


        $stillsRootDir = "/mnt/f/tmp2/format/wait/FFoutput_stills/{$taskM->id36}";
        if (file_exists($stillsRootDir))
        {
            exec("rm -rf {$stillsRootDir}");
        }


        if (file_exists($taskM->preLsCmdFileinfo->fullFilename))
        {
            @unlink($taskM->preLsCmdFileinfo->fullFilename);
        }
        if (file_exists($taskM->tmpLsCmdFileinfo->fullFilename))
        {
            @unlink($taskM->tmpLsCmdFileinfo->fullFilename);
        }

        if (file_exists($taskM->srcLsCmdFileinfo->fullFilename))
        {
            @unlink($taskM->srcLsCmdFileinfo->fullFilename);
        }
        if ($isRubish)
        {
            if (file_exists($taskM->dstLsCmdFileinfo->fullFilename))
            {
                @unlink($taskM->dstLsCmdFileinfo->fullFilename);
            }
        }

        usleep(20000);
        if (file_exists($taskM->srcLsCmdFileinfo->fullFilename))
        {
            $taskM->is_src_exist = Def::staYes;
        }
        else
        {
            $taskM->is_src_exist = Def::staNot;
        }

        if (file_exists($taskM->preLsCmdFileinfo->fullFilename))
        {
            $taskM->is_pre_exist = Def::staYes;
        }
        else
        {
            $taskM->is_pre_exist = Def::staNot;
        }
        if (file_exists($taskM->tmpLsCmdFileinfo->fullFilename))
        {
            $taskM->is_tmp_exist = Def::staYes;
        }
        else
        {
            $taskM->is_tmp_exist = Def::staNot;
        }


        $taskM->save();


        return ['input' => $this->inputBox->getCoreArray(), 'attrs' => $taskM->getAttributes(), 'videoFilename' => $taskM->dstLsCmdFileinfo->fullFilename, 'src_exist' => filesize( $taskM->srcLsCmdFileinfo->fullFilename)];
    }

}