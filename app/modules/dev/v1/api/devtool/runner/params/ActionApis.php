<?php

namespace modules\dev\v1\api\devtool\runner\params;

use hammer\web\ActionBase;
use modules\dev\v1\dao\devtool\RunnerApi;
use modules\dev\v1\dao\devtool\RunnerApiHis;


class ActionApis extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $data = [];
        $daos = RunnerApi::model()->findAll();
        $rows = [];
        foreach ($daos as $dao)
        {
            $rows[] = $dao->getOpenInfo();
        }
        return $rows;

    }

}
