<?php

namespace modules\spider\v1\api\boss_zhipin\spider;

use hammer\web\ActionBase;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionUploadSalaryImg extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        //var_dump($this->inputBox->getCoreArray(), $this->getRawJsonData());

        $jobs = $this->getRawJsonData();


        $jobDao     = new BossJob();
        $companyDao = new BossComp();

        $records = [];
        $errors  = [];

        $comIds = [];
        $jobIds = [];
        foreach ($jobs as $i => $job)
        {
            $comIds[] = $job['company']['id'];
            $jobIds[] = $job['jobId'];
        }

        $comDaos = $companyDao->findAll(['com_id' => $comIds]);
        $jobDaos = $jobDao->findAll(['job_id' => $jobIds]);
        //  $notExistComIds = array_diff($comIds, array_map(function ($item) { return $item->com_id; }, $comDaos));
        //  $notExistJobIds = array_diff($jobIds, array_map(function ($item) { return $item->job_id; }, $jobDaos));

        $comId2Dao = array_combine(array_map(function ($item) { return $item->com_id; }, $comDaos), $comDaos);
        $jobId2Dao = array_combine(array_map(function ($item) { return $item->job_id; }, $jobDaos), $jobDaos);

        try
        {
            foreach ($jobs as $i => $job)
            {
                $comId = $job['company']['id'];
                $jobId = $job['jobId'];
                if (isset($comId2Dao[$comId]))
                {
                    $comId2Dao[$comId]->spider_times = $comId2Dao[$comId]->spider_times + 1;
                }
                else
                {
                    $companyDao->clearRecord();
                    $companyDao->com_id = $job['company']['id'];
                    $companyDao->title  = $job['company']['name'];
                    $res                = $companyDao->save();
                }

                if (isset($jobId2Dao[$jobId]))
                {
                    $jobId2Dao[$jobId]->spider_times = $jobId2Dao[$jobId]->spider_times + 1;
                }
                else
                {
                    $jobDao->clearRecord();

                    $jobDao->job_id      = $job['jobId'];
                    $jobDao->title       = $job['name'];
                    $jobDao->com_id      = $job['company']['id'];
                    $jobDao->area_title  = $job['location']['name'];
                    $jobDao->salary_text = $job['salary'];
                    $jobDao->tags        = json_encode($job['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $jobDao->save();
                    $records[$i] = $jobDao->getOpenInfo();
                }


            }
        } catch (\Exception $e)
        {
            var_dump($e->getMessage());
        }

        return ['records' => $records, 'errors' => $errors];


    }

    public function isDebug()
    {
        return true;
    }
}