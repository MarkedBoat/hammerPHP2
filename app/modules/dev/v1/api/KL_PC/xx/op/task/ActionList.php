<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionList extends ActionBase
{
    //后期静态绑定代替了

    public function run()
    {

        $attrs  = $this->inputBox->getNotEmptyArray('attrs');
        $key    = [];
        $bindAr = [];
        $sqlAr  = [];

        $limit = intval($attrs['limit'] ?? 100);
        unset($attrs['limit']);

        $isNeedListSrcFilesSta = $attrs['listSrcFile'] === 'yes';
        unset($attrs['listSrcFile']);
        $sorts = [];
        for ($i = 1; $i <= 3; $i++)
        {
            $ak      = "sortAttr{$i}";
            $tk      = "sortType{$i}";
            $sorts[] = "`{$attrs[$ak]}` {$attrs[$tk]}";
            unset($attrs[$ak], $attrs[$tk]);
        }
        $sortStr = join(',', $sorts);

        foreach ($attrs as $attr => $val)
        {
            if (strstr($val, '>') || strstr($val, '<') || strstr($val, '='))
            {
                $sqlAr[] = "`{$attr}` {$val}";
            }
            else if (strstr($val, 'like'))
            {
                $val     = trim(str_replace('like', '', $val));
                $sqlAr[] = "`{$attr}` LIKE '%{$val}%'";
            }
            else
            {
                $sqlAr[] = "`{$attr}`={$val}";
            }
        }
        $sql     = join(' and ', $sqlAr);
        $rootDir = "/mnt/f/tmp2/format/wait/src";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $sql     = "select * from {$tn} where {$sql} order by {$sortStr}  limit {$limit}";
        //die($sql);
        $rows = $taskM->getConnection()->setText($sql)->queryAll();

        $existIds    = [];
        $notExistIds = [];
        foreach ($rows as $i => $row)
        {
            $rows[$i]['src_video_info'] = json_decode($row['src_video_info'], true);
            $rows[$i]['dst_video_info'] = json_decode($row['dst_video_info'], true);
            $rows[$i]['task_info']      = json_decode($row['task_info'], true);

        }

        if (count($existIds) > 0)
        {
            $idsStr = join(',', $existIds);
            $taskM->getConnection()->setText("update {$tn} set is_src_exist=1 where id in ({$idsStr})")->execute();
        }
        if (count($notExistIds) > 0)
        {
            $idsStr = join(',', $notExistIds);
            $taskM->getConnection()->setText("update {$tn} set is_src_exist=2 where id in ({$idsStr})")->execute();
        }
        // die;
        $kv = [];
        if ($isNeedListSrcFilesSta)
        {
            $kv  = $taskM->getId36KV($rootDir);
            $ids = array_map(function ($id36)
            {
                return base_convert($id36, 36, 10);
            }, array_keys($kv));
            if (count($ids) > 0)
            {
                $idsStr = join(',', $ids);
                $taskM->getConnection()->setText("update {$tn} set is_src_exist=1 where id in ({$idsStr}) and is_src_exist=2")->execute();
            }
        }
        else
        {
            $kv = new \stdClass();
        }
        return ['list' => $rows, 'rootDir' => $rootDir, 'sql' => $sql, 'srcVideoInfoKV' => $kv];
    }

}