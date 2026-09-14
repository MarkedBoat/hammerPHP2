<?php

namespace modules\spider\v1\api\boss_zhipin\spider;

use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\common\Def;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionSubmitDetail extends ActionBase
{
    // protected $inputType = 'JSON';
    //后期静态绑定代替了

    /**
     * @throws \Exception
     */
    public function run()
    {
        ini_set('memory_limit', '2048M');

        $jobInfo = json_decode($this->inputBox->getNotEmptyString('json'), true);


        //$jobInfo = $this->getRawJsonData();
        //  $jobInfo = $this->inputBox->getCoreArray();
        // Sys::app()->letFileLogging(true)->log(json_encode($jobInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        Sys::app()->getLogger()->tmpLetFileLogging();
        Sys::app()->log('ActionSubmitDetail', $jobInfo);
        // jobId: _jobInfo.job_id || '',
        //                    salary: _jobInfo.job_salary || '',
        //                    areaCode: _jobInfo.position || '',
        //                    remark: '',
        //                    hrActiveTime: '',
        //                    hrTitle: '',


        // var_dump($this->inputBox->getCoreArray(), $_FILES);
        //var_dump($jobs, $_FILES);

        $jobDao = new BossJob();

        $records = [];
        $errors  = [];


        $jobDao = $jobDao->findByAttributes(['job_id' => $jobInfo['jobId']]);


        if (empty($jobDao))
        {
            $jobDao         = new BossJob();
            $jobDao->job_id = $jobInfo['jobId'];
            $jobDao->title  = $jobInfo['jobName'];
            //$this->setMsg("职位不存在！[{$jobInfo['jobId']}]")->outError();
        }

        //这几个是从页面获得的，为了防止重定向一类的，记录下来
        $jobDao->com_id2     = $jobInfo['companyId'];
        $jobDao->company1    = $jobInfo['company'];
        $jobDao->company2    = $jobInfo['company2'];
        $jobDao->area_title2 = $jobInfo['comAddress'];


        $attrs = ['salary' => 'salary_text', 'areaCode' => 'area_code', 'remark' => 'detail', 'hrActiveTime' => 'hr_active_time', 'hrTitle' => 'hr_title'];
        foreach ($attrs as $attr => $prop)
        {
            if (isset($jobInfo[$attr]))
            {
                $jobDao->$prop = trim($jobInfo[$attr]);
            }
        }

        if (isset($jobInfo['isDog']))
        {
            $jobDao->is_self_biz = Def::staNot;
        }
        if (isset($jobInfo['tags']) && is_array($jobInfo['tags']))
        {
            $tags         = $jobDao->tags;
            $tags         = array_unique(array_merge($tags, $jobInfo['tags']));
            $jobDao->tags = json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }


        $jobDao->is_boss_fav  = intval($jobInfo['isBossFav'] ?? 2);
        $jobDao->spider_date  = date('Y-m-d H:i:s');
        $jobDao->spider_times = intval($jobDao->spider_times) + 1;

        if (isset($jobInfo['hrActiveTime']))
        {
            if (in_array($jobInfo['hrActiveTime'], ['2周内活跃', '本月活跃', '2月内活跃', '3月内活跃', '4月内活跃', '5月内活跃', '近半年活跃', '半年前活跃', ''], true))
            {
                $jobDao->is_hr_live = Def::staNot;
            }
            else
            {
                $jobDao->is_hr_live = Def::staYes;
            }
        }

        if (isset($jobInfo['isDel']) && $jobInfo['isDel'] === true)
        {
            $jobDao->is_jd_ol = Def::staNot;
        }
        else
        {
            $jobDao->is_jd_ol = Def::staYes;
        }

        $jobDao->save();

        return ['record' => $jobDao->getOpenInfo(), 'errors' => $errors];


    }

    public function isDebug()
    {
        return true;
    }
}