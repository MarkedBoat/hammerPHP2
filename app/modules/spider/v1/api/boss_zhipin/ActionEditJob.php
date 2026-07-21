<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\common\Def;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionEditJob extends ActionBase
{
    protected $inputType = 'JSON';

    /**
     * @throws \Exception
     */
    public function run()
    {


        $jobPK = $this->inputBox->getNotEmptyString('id');
        $attrs = $this->inputBox->getNotEmptyArray('attrs');

        Sys::app()->setFileLog(true)->log('editJob', $this->inputBox->getCoreArray());
        $jobDao = new BossJob();

        $errors = [];

        $jobDao = $jobDao->findByPk($jobPK);


        if (empty($jobDao))
        {
            $this->setMsg("职位不存在！[{$jobPK}]")->outError();
        }


        foreach ($attrs as $attr => $val)
        {
            $jobDao->$attr = $val;
        }
        if (isset($attrs['deny_reason']))
        {
            if (strstr($attrs['deny_reason'], '外包'))
            {
                $jobDao->is_self_biz = Def::staNot;
            }
            if (strstr($attrs['deny_reason'], '不匹配'))
            {
                $jobDao->is_match = Def::staYes;
            }

        }

        $jobDao->save();

        return $jobDao->getOpenInfo();


    }

    public function isDebug()
    {
        return true;
    }
}