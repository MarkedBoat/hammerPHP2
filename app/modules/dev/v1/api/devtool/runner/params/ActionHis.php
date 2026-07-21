<?php

namespace modules\dev\v1\api\devtool\runner\params;

use hammer\web\ActionBase;
use modules\dev\v1\dao\devtool\RunnerApiHis;


class ActionHis extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $data = [];
        if ($this->getInputBox()->tryGetString('prefix'))
        {
            $data['domain_prefix'] = $this->getInputBox()->tryGetString('prefix');
        }
        if ($this->getInputBox()->tryGetString('api_name'))
        {
            $data['api_name'] = $this->getInputBox()->tryGetString('api_name');
        }
        if ($this->getInputBox()->tryGetInt('ver'))
        {
            $data['ver'] = $this->getInputBox()->tryGetInt('ver');
        }
        $order_by=$this->getInputBox()->getNotEmptyString('order_by');
        $daos = RunnerApiHis::model()->order(" order by {$order_by} desc ")->findAllByAttributes($data);
        $rows = [];
        foreach ($daos as $dao)
        {
            $rows[] = $dao->getOpenInfo();
        }
        return $rows;

    }

}