<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\db\DbQuery;
use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionCompanies extends ActionBase
{
    //后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {

        return DbQuery::m(new BossComp())->setSelects(['id', 'com_id', 'title'])->setInputDataBox($this->inputBox)->queryPageData();

    }

    public function isDebug()
    {
        return false;
    }
}