<?php

namespace modules\spider\v1\api\boss_zhipin;

use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\common\Def;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionSubmitJobsList extends ActionBase
{
    //后期静态绑定代替了

    public function run()
    {

        $sourceFlag = $this->inputBox->tryGetInt('sourceFlag');
        $jobs       = json_decode($this->inputBox->getNotEmptyString('json'), true);
        Sys::app()->setFileLog(true)->log(json_encode($jobs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $reffer = $_SERVER['HTTP_REFERER'] ?? 'empty reffer';
        $date   = date('Y-m-d H:i:s');
        //file_put_contents(__HAMMER_DIR__ . '/uploads/jobs.jsons', "\n#date:{$date}\n#reffer:{$reffer}" . json_encode($jobs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), FILE_APPEND);


        // var_dump($this->inputBox->getCoreArray(), $_FILES);
        //var_dump($jobs, $_FILES);

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

        $allCnt    = 0;
        $insertCnt = 0;
        $updateCnt = 0;
        //$jobIds    = [];
        try
        {
            foreach ($jobs as $i => $job)
            {
                $allCnt += 1;
                $comId  = $job['company']['id'];
                $jobId  = $job['jobId'];
                //  $jobIds[] = $jobId;
                if (isset($comId2Dao[$comId]))
                {
                    $comId2Dao[$comId]->spider_times = $comId2Dao[$comId]->spider_times + 1;
                    $comId2Dao[$comId]->save();
                }
                else
                {
                    $companyDao->clearRecord();
                    $companyDao->com_id = $job['company']['id'];
                    $companyDao->title  = $job['company']['name'];
                    $res                = $companyDao->save();

                }

                $jobPk = 0;
                if (isset($jobId2Dao[$jobId]))
                {
                    $jobId2Dao[$jobId]->spider_times = $jobId2Dao[$jobId]->spider_times + 1;
                    $jobId2Dao[$jobId]->is_jd_ol     = Def::staYes;
                    $jobId2Dao[$jobId]->source_flag  = intval($jobId2Dao[$jobId]->source_flag) | $sourceFlag;

                    $records[$jobId] = $jobId2Dao[$jobId]->getOpenInfo();
                    $jobId2Dao[$jobId]->save();
                    $jobPk     = $jobId2Dao[$jobId]->id;
                    $jobDao    = $jobId2Dao[$jobId];
                    $updateCnt += 1;
                }
                else
                {
                    $jobDao->clearRecord();
                    //if((((`is_php` = 2) and (`is_match` = 2)) or (`is_ok` = 2) or (`is_self_biz` = 2) or (`is_hr_live` = 2) or (`is_true` = 2) or (`is_edu_ok` = 2) or (`is_jd_ol` = 2) or (length(`deny_reason`) > 4)),1,2)
                    //if(   ( `is_php` = 1 or `is_match` = 1 ) and `is_self_biz` = 1 and `is_hr_live` = 1 and `is_true` = 1 and `is_edu_ok` = 1 and `is_jd_ol` = 1 and length(`deny_reason`) < 4,1,2)
                    $jobDao->job_id         = $job['jobId'];
                    $jobDao->title          = $job['name'];
                    $jobDao->com_id         = $job['company']['id'];
                    $jobDao->area_title     = $job['location']['name'];
                    $jobDao->deny_reason    = '';
                    $jobDao->hr_title       = '';
                    $jobDao->hr_active_time = '';
                    $jobDao->source_flag    = $sourceFlag;

                    // $jobDao->salary_text = $job['salary'];
                    $jobDao->tags = json_encode($job['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $jobDao->save();
                    $records[$jobDao->job_id] = $jobDao->getOpenInfo();
                    $jobPk                    = $jobDao->id;
                    $insertCnt                += 1;
                }
                if (0)
                {
                    if (isset($_FILES["screenshot_{$i}"]) && $_FILES["screenshot_{$i}"]['error'] == 0)
                    {
                        $newMd5 = md5_file($_FILES["screenshot_{$i}"]['tmp_name']);
                        if ($newMd5 !== strval($jobDao->salary_shoot_md5))
                        {
                            move_uploaded_file($_FILES["screenshot_{$i}"]['tmp_name'], __HAMMER_DIR__ . "/uploads/boss-zhipin/salary_pngs/{$jobPk}.png");
                            $jobDao->salary_shoot_md5 = $newMd5;
                            $jobDao->save();
                        }

                    }
                }


            }
        } catch (\Exception $e)
        {
            var_dump($e->getMessage());
            throw $e;
        }
        $uniqJobIds = array_unique($jobIds);
        $uniqCnt    = count($uniqJobIds);
        return ['jobsCnt' => count($jobs), 'all' => $allCnt, 'repeat' => $allCnt - $uniqCnt, 'uniq' => $uniqCnt, 'insert' => $insertCnt, 'update' => $updateCnt];

        return ['records' => $records, 'errors' => $errors, 'files' => $_FILES];


    }

    public function isDebug()
    {
        return true;
    }
}