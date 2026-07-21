<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\db\DbQuery;
use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionGetJobMd5Times extends ActionBase
{

    protected $inputType = 'JSON';

    /**
     * @throws \Exception
     */
    public function run()
    {

        Sys::app()->setDebug(true);
        $jobDao = new BossJob();
        $jobTn  = $jobDao->getTableName();
        $inBind = $this->inputBox->getNotEmptyArray('job_md5s');
        $inSqls = array_fill(0, count($inBind), '?');

        $inSqlCode = implode(',', $inSqls);

        $rows       = $jobDao->getConnection()->getOldPdoCmd()->queryRows("select job_md5,count(id) as times from {$jobTn} where job_md5 in ($inSqlCode) group by job_md5", $inBind);
        $times2md5s = [];
        foreach ($rows as $row)
        {
            if ($row['times'] > 1)
            {
                if (!isset($times2md5s[$row['times']]))
                {
                    $times2md5s[$row['times']] = [];
                }
                $times2md5s[$row['times']][] = $row['job_md5'];
            }
        }
        foreach ($times2md5s as $times => $md5s)
        {
            $inSqlCode = implode(',', array_fill(0, count($md5s), '?'));
            $jobDao->getConnection()->getOldPdoCmd()->execute("update {$jobTn} set job_md5_cnt={$times} where job_md5 in ($inSqlCode)", $md5s);
        }

        return $rows;

    }

    public function isDebug()
    {
        return false;
    }
}