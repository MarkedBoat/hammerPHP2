<?php

namespace modules\dev\v1\api\KL_PC\xx\op\task;

use hammer\web\ActionBase;
use models\common\Def;
use modules\dev\v1\dao\video\FfmpegTask;


class ActionOkList extends ActionBase
{
    //后期静态绑定代替了

    public function run()
    {
        $rootDir = "/mnt/f/tmp2/format/wait";

        $dstRootDir = "/mnt/f/tmp2/format/wait/FFoutput_res";
        $tmpRootDir = "/mnt/f/tmp2/format/wait/FFoutput_tmp";
        $preRootDir = "/mnt/f/tmp2/format/wait/FFoutput_pre";

        $srcRootDir = "/mnt/f/tmp2/format/wait/src";

        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();

        $sql  = "select * from {$tn} where is_ok=1 and is_src_exist=1 and album_dir='' order by id asc";
        $rows = $taskM->getConnection()->setText($sql)->queryAll();

        $sql  = "select * from {$tn} where is_ok=1 and is_src_exist=1 and album_dir!='' order by album_dir asc,video_title asc";
        $rows += $taskM->getConnection()->setText($sql)->queryAll();


        $kv2kv   = [
            'src' => $taskM->getId36KV($srcRootDir),
            'dst' => $taskM->getId36KV($dstRootDir),
            'tmp' => $taskM->getId36KV($tmpRootDir),
            'pre' => $taskM->getId36KV($preRootDir),
        ];
        $id362kv = [];
        foreach ($kv2kv as $type => $id36kv)
        {
            foreach ($id36kv as $id36 => $info)
            {
                if (!isset($id362kv[$id36]))
                {
                    $id362kv[$id36] = [
                        'src' => [],
                        'dst' => [],
                        'tmp' => [],
                        'pre' => [],
                    ];
                }
                $id362kv[$id36][$type] = $info;
            }
        }

        foreach ($rows as $i => $row)
        {
            $rows[$i]['src_video_info'] = json_decode($row['src_video_info'], true);
            $rows[$i]['dst_video_info'] = json_decode($row['dst_video_info'], true);
            $rows[$i]['tmp_video_info'] = json_decode($row['pre_video_info'], true);
            $rows[$i]['pre_video_info'] = json_decode($row['tmp_video_info'], true);

            $rows[$i]['task_info']       = json_decode($row['task_info'], true);
            $rows[$i]['_dst_video_info'] = $row['dst_video_info'];

            if (strlen($row['album_dir']) > 0)
            {
                $albumDirPath = "/{$row['album_dir']}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $srcVideoFilename    = "{$rootDir}{$albumDirPath}{$row['video_title']}.{$row['video_ext']}";
            $relativeResFilename = $rows[$i]['dst_video_info']['src'] ?? '';
            if (intval($row['is_pre']) === Def::staYes)
            {
                $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
                $dstVideoFilename = "{$rootDir}{$albumDirPath}{$row['video_title']} ^{$row['36']}^.mp4";
            }
            else
            {
                $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
            }

            $rows[$i]['is_src_exist'] = file_exists($srcVideoFilename) ? Def::staYes : Def::staNot;
            $rows[$i]['filesize']     = $rows[$i]['is_src_exist'] === Def::staYes ? filesize($srcVideoFilename) : 0;
            $rows[$i]['src']          = $srcVideoFilename;
            $rows[$i]['dst']          = $dstVideoFilename;
            $rows[$i]['is_dst_exist'] = file_exists($dstVideoFilename) ? Def::staYes : Def::staNot;
            $rows[$i]['dstFilesize']  = $rows[$i]['is_dst_exist'] === Def::staYes ? filesize($dstVideoFilename) : 0;

            if (isset($id362kv[$row['id36']]))
            {
                $rows[$i]['fileKv'] = $id362kv[$row['id36']];
                if(isset($id362kv[$row['id36']]['src']))
                {
                    $srcVideoFilename = $id362kv[$row['id36']]['src']->fullFilename;

                    $rows[$i]['is_src_exist'] = $id362kv[$row['id36']]['src']->filesize > 0;
                    $rows[$i]['filesize']     = $id362kv[$row['id36']]['src']->filesize;
                    $rows[$i]['src']          = $srcVideoFilename;
                }
                if(!empty($id362kv[$row['id36']]['dst']))
                {
                    $dstVideoFilename = $id362kv[$row['id36']]['dst']->fullFilename;

                    $rows[$i]['dst']          = $dstVideoFilename;
                    $rows[$i]['is_dst_exist'] = $id362kv[$row['id36']]['dst']->filesize > 0;
                    $rows[$i]['dstFilesize']  = $id362kv[$row['id36']]['dst']->filesize ;
                }
            }


        }
        // die;

        return ['list' => $rows, 'rootDir' => $rootDir,'$kv2kv'=>$kv2kv];
    }

}