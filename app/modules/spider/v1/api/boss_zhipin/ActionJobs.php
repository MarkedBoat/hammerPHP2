<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\db\DbQuery;
use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionJobs extends ActionBase
{


    public function run()
    {
        Sys::app()->setDebug(true);
        $inputBox = $this->getInputBoxFromPostBody();
        $attrs    = $inputBox->tryGetArray('attr');
        $page     = new DataBox($inputBox->tryGetArray('page'));

        $pageIndex = $page->defaulVal(1)->tryGetInt('no');
        $pageSize  = $page->defaulVal(10)->tryGetInt('size');
        $sort      = $inputBox->tryGetArray('sort');
        // var_dump($attrs,$this->inputBox->tryGetArray('page'),$page->getCoreArray(),$pageSize,$pageIndex);die;


        // var_dump($this->inputBox->getCoreArray(), $_FILES);
        //var_dump($jobs, $_FILES);

        $jobDao     = new BossJob();
        $companyDao = new BossComp();
        $query      = DbQuery::m($jobDao);
        $query->setWheres($attrs)->setLimit($pageIndex, $pageSize)->setOrderFiled2Type($inputBox->tryGetArray('sort'));

        return $query->queryPageData();


    }

    public function isDebug()
    {
        return true;
    }
}