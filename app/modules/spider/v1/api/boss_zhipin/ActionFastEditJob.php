<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\param\DataBox;
use hammer\web\ActionBase;
use models\common\Def;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionFastEditJob extends ActionBase
{
    protected $inputType = 'JSON';

    /**
     * @throws \Exception
     */
    public function run()
    {


        $jobPK  = $this->inputBox->getNotEmptyString('id');
        $field  = $this->inputBox->getNotEmptyString('field');
        $value  = $this->inputBox->getNotEmptyString('value');
        $jobDao = new BossJob();
        $jobDao = $jobDao->findByPk($jobPK);
        if (empty($jobDao))
        {
            $this->setMsg("职位不存在！[{$jobPK}]")->outError();
        }
        $jobDao->$field = $value;

        $jobDao->save();

        return ['record' => $jobDao->getOpenInfo()];


    }

    public function isDebug()
    {
        return true;
    }
}