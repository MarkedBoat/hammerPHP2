<?php

namespace modules\dev\v1\api\KL_PC\xx\op\config;

use hammer\web\ActionBase;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionKv extends ActionBase
{

    //后期静态绑定代替了

    public function run()
    {

        @ob_clean();
        $rootDir         = '/mnt/f/tmp2/format/wait/src';
        $hdItemsFilename = __APP_DIR__ . '/config/file/xx_video/hd_items.json';
        $hdItems         = [];
        $isExist         = false;
        if (!file_exists($hdItemsFilename))
        {
            $hdItems = json_decode('[{"height":720,"maxRateK":1600,"fps":24,"title":"骑兵/标准"},{"height":720,"maxRateK":2000,"fps":24,"title":"720 2000k 24fps"},{"height":720,"maxRateK":2400,"fps":30,"title":"720高帧"},{"height":720,"maxRateK":2400,"fps":24,"title":"高清720"},{"height":720,"maxRateK":3000,"fps":30,"title":"超清清720 30fps"},{"height":720,"maxRateK":3000,"fps":24,"title":"超清清720 24fps"},{"height":1080,"maxRateK":1600,"fps":24,"title":"骑兵/标准/低质手机"},{"height":1080,"maxRateK":2000,"fps":24,"title":"1080P 2M 24fps"},{"height":1080,"maxRateK":2400,"fps":24,"title":"普通步兵1080"},{"height":1080,"maxRateK":3000,"fps":30,"title":"顶级步兵1080"}]', true);
        }
        else
        {
            $isExist = true;
            $hdItems = json_decode(file_get_contents($hdItemsFilename), true);
        }

        $taskM = new FfmpegTask();
        // 截图时间点（开始时间，单位：秒）
        $startTsList = array_column($taskM->getConnection()->setText("SELECT distinct task_info->>'$.manual.ss' as sec FROM kl.ffmpeg_tasks where is_ok=1 and json_extract(task_info,'$.manual.ss') is not null;")->queryAll(), 'sec');
        // 结尾偏移量（单位：秒），实际截图时间 = 视频总时长 - 该值
        $tailTsList = array_column($taskM->getConnection()->setText("SELECT distinct task_info->>'$.manual.tailAdStart' as sec FROM kl.ffmpeg_tasks where is_ok=1 and json_extract(task_info,'$.manual.tailAdStart') is not null ;")->queryAll(), 'sec');


        return [
            'hdItems'        => $hdItems,
            'x'              => $isExist,
            'headerTs'       => array_map(function ($v) { return intval($v); }, $startTsList),
            'tailTs'         => array_map(function ($v) { return intval($v); }, $tailTsList),
          //  'srcVideoInfoKV' => $taskM->getId36KV($rootDir, []),
            //'resVideoInfoKV' => $taskM->getId36KV($rootDir . '/FFOutput_res', []),
        ];

        \hammer\web\HttpApp::$hasOutput = true;
        die(file_get_contents($filename));


    }

}