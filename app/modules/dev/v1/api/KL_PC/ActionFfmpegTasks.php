<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionFfmpegTasks extends ActionBase
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
        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();
        $sql     = "select * from {$tn} where {$sql} order by album_dir asc,video_basename asc,run_lc asc";
        $rows    = $taskM->getConnection()->setText($sql)->queryAll();

        $existIds    = [];
        $notExistIds = [];
        foreach ($rows as $i => $row)
        {
            $rows[$i]['src_video_info'] = json_decode($row['src_video_info'], true);
            $rows[$i]['dst_video_info'] = json_decode($row['dst_video_info'], true);
            $rows[$i]['task_info']      = json_decode($row['task_info'], true);

            if (strlen($row['album_dir']) > 0)
            {
                $albumDirPath = "/{$row['album_dir']}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $video_path = "{$rootDir}{$albumDirPath}{$row['video_basename']}.{$row['video_ext']}";
            if (file_exists($video_path))
            {
                $rows[$i]['filesize'] = filesize($video_path);
                $rows[$i]['src']      = $video_path;
                if (intval($row['is_src_exist']) !== Def::staYes)
                {
                    $existIds[]               = $row['id'];
                    $rows[$i]['is_src_exist'] = Def::staYes;
                }
            }
            else
            {
                $rows[$i]['filesize'] = 0;
                $rows[$i]['src']      = '';
                if (intval($row['is_src_exist']) === Def::staYes)
                {
                    $notExistIds[]            = $row['id'];
                    $rows[$i]['is_src_exist'] = Def::staNot;
                }
            }

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


        return ['list' => $rows, 'rootDir' => $rootDir];
    }

}