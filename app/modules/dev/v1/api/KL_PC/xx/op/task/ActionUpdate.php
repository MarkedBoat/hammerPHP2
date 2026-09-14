<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task;

use hammer\param\DataBox;
use hammer\param\Params;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionUpdate extends ActionBase
{
    //后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {
        Sys::app()->setDebug(true);

        $rootDir = "/mnt/f/tmp2/format/wait/src";



        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();
        $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));


        $attrs = $this->inputBox->tryGetArray('attrs');
        if (count($attrs) > 0)
        {
            foreach ($attrs as $attr => $val)
            {
                $taskM->$attr = $val;
            }

            if (intval($attrs['is_pre'] ?? 0) === Def::staYes)
            {
                $taskM->is_ok = Def::staNot;
                $attrs['is_ok'] = Def::staNot;
            }
            if (intval($attrs['is_ok'] ?? 0) === Def::staNot)
            {
                $taskM->getLsCmdFileinfos($rootDir);
                if ($taskM->srcLsCmdFileinfo->filesize > 0)
                {

                    if (file_exists($taskM->preLsCmdFileinfo->fullFilename))
                    {
                        @unlink($taskM->preLsCmdFileinfo->fullFilename);
                    }
                    if (file_exists($taskM->tmpLsCmdFileinfo->fullFilename))
                    {
                        @unlink($taskM->tmpLsCmdFileinfo->fullFilename);
                    }
                    if (file_exists($taskM->dstLsCmdFileinfo->fullFilename))
                    {
                        @unlink($taskM->dstLsCmdFileinfo->fullFilename);
                    }
                    $taskM->is_src_exist = Def::staYes;
                    $taskM->is_ok        = Def::staNot;
                }
                else
                {
                    $taskM->is_src_exist = Def::staNot;
                }
            }
        }
        else
        {
            $tmp     = $taskM->task_info;
            $task    = $this->inputBox->getNotEmptyArray('task');
            $taskBox = new DataBox($task);
            if ($taskBox->tryGetString('isCopy') === 'true')
            {
                $tmp['expect'] = ['copy' => true, 'resAs' => $taskBox->getExistedString('resAs'), 'resVer' => $taskBox->getExistedString('resVer'),] + $task;
            }
            else
            {
                if (isset($tmp['expect']['copy']))
                {
                    unset($tmp['expect']['copy']);
                }
            }

            $tmp['manual'] = $task;
            if (isset($task['ext']['isPre']))
            {
                $taskM->is_pre = $task['ext']['isPre'];
            }

            $taskM->task_info = $tmp;
        }
        $taskM->save();

        return ['input' => $this->inputBox->getCoreArray(), 'task' => $taskM->getAttributes()];
    }

}