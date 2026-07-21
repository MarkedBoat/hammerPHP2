<?php

namespace modules\dev\v1\api\devtool\runner\args\keywords;

use hammer\web\ActionBase;
use modules\dev\v1\dao\devtool\DebugKwDao;


class ActionList extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $daos = DebugKwDao::model()->findAllByAttributes(['domain_prefix' => $this->getInputBox()->getNotEmptyString('prefix'), 'api_name' => $this->getInputBox()->getNotEmptyString('api_name')]);
        $rows = [];
        foreach ($daos as $dao)
        {
            $rows[] = $dao->getOpenInfo();
        }
        return $rows;

    }

}