<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\param\DataBox;
use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionGetDetailTasks extends ActionBase
{
    //后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {


        $todayZeroDate = date('Y-m-d 00:00:00', intval(time() / 86400) * 86400);
        $jobDao        = new BossJob();
        $tn            = $jobDao->getTableName();
        $sql           = "select id,job_id,title from {$tn} where 
                                      is_ok=1 and (
                                          ( is_md5_old=2 and   update_date<'{$todayZeroDate}' ) or detail is null
                                          )   order by id desc";
        if($this->inputBox->getNotEmptyString('detailNullOnly')==='yes')
        {
            $sql="select id,job_id,title from {$tn} where    is_ok=1 and   detail is null  order by id desc";
        }else{
            $sql="
select id,job_id,title from {$tn} where  is_ok=1 and   detail is null 
union 
select id,job_id,title from {$tn} where  is_ok=1 and   is_md5_old=2 and   update_date<'{$todayZeroDate}'   
order by id desc;";
        }
        $jobs = $jobDao->getConnection()->setText($sql)->queryAll();
        //$jobs   = $jobDao->getConnection()->setText("select id,job_id,title from {$tn} where detail is null")->queryAll();

        return ['sql' => str_replace("\n", '', $sql), 'jobs' => $jobs];


    }

    public function isDebug()
    {
        return true;
    }
}