<?php

namespace modules\dev\v1\api\devtool\runner\args\keywords;

use hammer\web\ActionBase;
use hammer\sys\Sys;
use modules\dev\v1\dao\devtool\DebugKwDao;


class ActionAdd extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $dao=new DebugKwDao();
        $dao->domain_prefix=$this->getInputBox()->getNotEmptyString('prefix');
        $dao->ver=$this->getInputBox()->getInt('version');
        $dao->api_name=$this->getInputBox()->getNotEmptyString('api_name');
        $dao->args_json=$this->getInputBox()->getNotEmptyString('args_json');
        $dao->title=$this->getInputBox()->getNotEmptyString('title');
        $dao->brief=$this->getInputBox()->getNotEmptyString('brief');
       // id, domain_prefix, ver, api_name, args_json, title, brief, isdel, create_at, update_at

        $dao->save();
        return $dao->getOpenInfo();


    }

}