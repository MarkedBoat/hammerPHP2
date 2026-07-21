<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\param\DataBox;
use hammer\web\ActionBase;
use models\common\Def;
use hammer\param\Params;
use hammer\sys\Sys;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionFfmpegTaskUpdate extends ActionBase
{
//后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {
        Sys::app()->setDebug(true);

        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $taskM->findByPk($this->inputBox->getNotEmptyInt('id'));


        $attrs = $this->inputBox->tryGetArray('attrs');
        if (count($attrs) > 0)
        {
            foreach ($attrs as $attr => $val)
            {
                $taskM->$attr = $val;
            }
            if (intval($attrs['is_ok'] ?? 0) === Def::staNot)
            {
                $srcVideoInfo = $taskM->getSrcVideoInfo($rootDir);

                if ($srcVideoInfo['filesize'] > 0)
                {
                    $dstVideoInfo = $taskM->getDstVideoInfo($rootDir);
                    if ($dstVideoInfo['filesize'] > 0)
                    {
                        unset($dstVideoInfo['filename']);
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

            $taskM->task_info = $tmp;
        }
        $taskM->save();

        return ['input' => $this->inputBox->getCoreArray(), 'task' => $taskM->getAttributes()];
    }

}