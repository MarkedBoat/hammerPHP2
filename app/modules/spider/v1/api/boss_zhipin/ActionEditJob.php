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
        ini_set('memory_limit', '2048M');


        $jobPK = $this->inputBox->getNotEmptyString('id');
        $attrs = $this->inputBox->getNotEmptyArray('attrs');

        Sys::app()->letFileLogging(true)->log('editJob', $this->inputBox->getCoreArray());
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
        $comInfo = [];
        if ($jobDao->is_out_src === Def::staYes || $jobDao->is_saas === Def::staYes || $jobDao->is_age_ok === Def::staNot)
        {
            $comDao = BossComp::model()->findByAttributes(['com_id' => $jobDao->com_id]);
            if (empty($comDao))
            {
                $comInfo[] = "empty comDao";
            }
            else
            {
                if ($comDao->is_out_src === Def::staNot && $jobDao->is_out_src === Def::staYes)
                {
                    $comDao->is_out_src = Def::staYes;
                }
                else
                {
                    $comInfo[] = "comDao is not out src v:{$comDao->is_out_src}";
                }

                if ($comDao->is_saas === Def::staNot && $jobDao->is_saas === Def::staYes)
                {
                    $comDao->is_saas = Def::staYes;
                }
                else
                {
                    $comInfo[] = "comDao is not saas v:{$comDao->is_saas}";
                }

                if ($comDao->is_age_ok === Def::staYes && $jobDao->is_age_ok === Def::staNot)
                {
                    $comDao->is_age_ok = Def::staNot;
                }
                else
                {
                    $comInfo[] = "comDao is not age ok v:{$comDao->is_age_ok}";
                }

                $comInfo = $comDao->getOpenInfo();
                $comDao->save();
            }
        }
        return ['job' => $jobDao->getOpenInfo(), 'company' => $comInfo];


    }

    public function isDebug()
    {
        return true;
    }
}