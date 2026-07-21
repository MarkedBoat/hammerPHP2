<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\param\DataBox;
use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionGetFillInfoTasks extends ActionBase
{
//后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {



        $todayZeroDate=date('Y-m-d 10:00:00',intval(time()/86400)*86400);
        $jobDao = new BossJob();
        $tn     = $jobDao->getTableName();
        $jobs   = $jobDao->getConnection()->setText("select id,job_id,title from {$tn} where 
                                      is_ok=1 and (
                                          ( is_md5_old=2 and   update_date<'{$todayZeroDate}' ) or detail is null
                                          )   order by id desc")->queryAll();
        //$jobs   = $jobDao->getConnection()->setText("select id,job_id,title from {$tn} where detail is null")->queryAll();

        return ['jobs' => $jobs];


    }

    public function isDebug()
    {
        return true;
    }
}