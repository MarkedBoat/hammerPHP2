<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionFfmpegedList extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        $rootDir = "/mnt/f/tmp2/format/wait/php";
        $taskM   = new FfmpegTask();
        $tn      = $taskM->getTableName();

        $sql  = "select * from {$tn} where is_ok=1 and is_src_exist=1 and album_dir='' order by id asc";
        $rows = $taskM->getConnection()->setText($sql)->queryAll();

        $sql  = "select * from {$tn} where is_ok=1 and is_src_exist=1 and album_dir!='' order by album_dir asc,video_basename asc";
        $rows += $taskM->getConnection()->setText($sql)->queryAll();


        foreach ($rows as $i => $row)
        {
            $rows[$i]['src_video_info'] = json_decode($row['src_video_info'], true);
            $rows[$i]['dst_video_info'] = json_decode($row['dst_video_info'], true);
            $rows[$i]['task_info']      = json_decode($row['task_info'], true);
            $rows[$i]['_dst_video_info'] = $row['dst_video_info'];

            if (strlen($row['album_dir']) > 0)
            {
                $albumDirPath = "/{$row['album_dir']}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $srcVideoFilename    = "{$rootDir}{$albumDirPath}{$row['video_basename']}.{$row['video_ext']}";
            $relativeResFilename = $rows[$i]['dst_video_info']['src'] ?? '';
            if(intval($row['is_pre'])===Def::staYes){
                $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
                $dstVideoFilename = "{$rootDir}{$albumDirPath}{$row['video_basename']} kl{$row['id']}.mp4";
            }else{
                $dstVideoFilename = "{$rootDir}{$relativeResFilename}";

            }

            $rows[$i]['is_src_exist'] = file_exists($srcVideoFilename) ? Def::staYes : Def::staNot;
            $rows[$i]['filesize']     = $rows[$i]['is_src_exist'] === Def::staYes ? filesize($srcVideoFilename) : 0;
            $rows[$i]['src']          = $srcVideoFilename;
            $rows[$i]['dst']          = $dstVideoFilename;
            $rows[$i]['is_dst_exist'] = file_exists($dstVideoFilename) ? Def::staYes : Def::staNot;
            $rows[$i]['dstFilesize']  = $rows[$i]['is_dst_exist'] === Def::staYes ? filesize($dstVideoFilename) : 0;

        }
        // die;

        return ['list' => $rows, 'rootDir' => $rootDir];
    }

}