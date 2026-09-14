<?php


namespace cli\media;

use hammer\cli\CmdBase;
use hammer\sys\Sys;
use models\ext\tool\Curl;


class CmdTmp extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    ///porter_hammer media/tmp fm_play_md5 --env=poseidon_test  --input_file=/home/yangl_upload/Md5_success.log,/home/yangl_upload/Md5_success.part2.log --out_file=fm_play_md5.sql
    /// /porter_hammer media/tmp fm_play_md5 --env=poseidon_test  --input_files=/home/yangl_upload/md5_20200427_0.txt,/home/yangl_upload/md5_20200427_1.txt --out_file=fm_play_md5.sql
    public function fm_play_md5()
    {
        $input_files = explode(',', $this->inputBox->getNotEmptyString('input_files'));
        $out_file    = Sys::app()->params['cli']['logDir'] . '/' . $this->inputBox->getNotEmptyString('out_file');
        $fileLine    = 0;
        $sql_tpl     = "UPDATE `db_poseidon_media`.`fm_play` SET `md5`='{md5}' WHERE `infohash`='{hash}' AND `md5`='';";
        $str_cnt     = 0;
        $data_cnt    = 0;
        file_put_contents($out_file, '');
        foreach ($input_files as $input_file)
        {
            $f = fopen($input_file, 'r');
            while (!feof($f))
            {
                $fileLine++;
                $str = trim(fgets($f));
                if ($str)
                {
                    $str_cnt++;
                    //{"failure_type": "2:0", "ext": "", "result": "success", "msg": "download success", "size": "0", "broker_port": 9007, "id": "ECB58B8CCA77B1C51270CF9C97E00BA3AF1EA8DB", "md5": "68954f4c0804896e935eaa557bad9476"}
                    $data = json_decode($str, true);
                    if (isset($data['result']) && $data['result'] === 'success')
                    {
                        $data_cnt++;
                        $sql = str_replace(['{md5}', '{hash}'], [$data['md5'], $data['id']], $sql_tpl) . "\n";
                        file_put_contents($out_file, $sql, FILE_APPEND);
                        echo "{$data_cnt}/{$str_cnt}/{$fileLine}:{$sql}";
                    }
                }

            }
            fclose($f);
        }

        $text = Curl::upToContent($out_file);
        var_export(json_decode($text, true));
        echo "\nuploaded:{$out_file}\n";
        //var_dump($db->setText('show tables;')->queryAll());
        echo "\nok\n";

    }
// /porter_hammer media/tmp fm_play_empty_md5 --env=poseidon_test  --input_files=/home/yangl_upload/mid.csv --out_file=fm_play_empty_md5.csv
    public function fm_play_empty_md5()
    {
        $input_files  = explode(',', $this->inputBox->getNotEmptyString('input_files'));
        $out_file     = Sys::app()->params['cli']['logDir'] . '/' . $this->inputBox->getNotEmptyString('out_file');
        $input_line   = 0;
        $sql_get_ep   = "SELECT `episode_id` FROM db_poseidon_media.fm_episode where `media_id`=:mid;";
        $sql_get_play = "SELECT `infohash` FROM db_poseidon_media.fm_play where episode_id=:eid and md5='';";
        $meida_cnt    = 0;
        $episode_cnt  = 0;
        $play_cnt     = 0;
        file_put_contents($out_file, '');
        $cmd_get_ep   = Sys::app()->db_tmp('T_db_poseidon_media')->setText($sql_get_ep);
        $cmd_get_play = Sys::app()->db_tmp('T_db_poseidon_media')->setText($sql_get_play);
        foreach ($input_files as $input_file)
        {
            $f = fopen($input_file, 'r');
            while (!feof($f))
            {
                $input_line++;
                $mid = intval(fgets($f));
                if ($mid)
                {
                    $meida_cnt++;
                    $table_ep = $cmd_get_ep->bindArray([':mid' => $mid])->queryAll();
                    foreach ($table_ep as $row_ep)
                    {
                        $eid = $row_ep['episode_id'];
                        $episode_cnt++;
                        $table_play = $cmd_get_play->bindArray([':eid' => $eid])->queryAll();
                        foreach ($table_play as $row_play)
                        {
                            $hash = $row_play['infohash'];
                            $play_cnt++;
                            file_put_contents($out_file, "{$hash}\n", FILE_APPEND);
                            echo "{$meida_cnt}/{$episode_cnt}/{$play_cnt}:{$hash}\n";
                        }
                    }
                }
            }
            fclose($f);
        }


        //var_dump($db->setText('show tables;')->queryAll());
        echo "\nok\n";

    }

    public function tmp(){
        $tpl='    array(
                        "nav_id"     => "{n}",
                        "id"         => "{i}",
                        "code"       => "tv",
                        "template"   => "cstill",
                        "htemplate"  => "",
                        "streamplay" => "",
                        "name"       => "{a}",
                        "icon1"      => "",
                        "icon2"      => "",
                        "url"        => "",
                        "is_drag"    => "",
                        "is_common"  => "",
                        "color"      => "#ffffff",
                        "background" => "",
                    ),';
        $str='推荐,全部,0#社会,1,500#娱乐,2,501#历史,16,502#科技,11,503#汽车,12,504#财经,15,505#军事,5,506#情感,10,507#体育,6,508#健康,7,509#笑话,8,510#游戏,9,511#星座,13,512#时尚,14,513';
        $ar=explode('#',$str);
        foreach ($ar as $str1){
            $ar2=explode(',',$str1);
            $str2=str_replace(['{n}','{i}','{a}'],[$ar2[1],$ar2[2],$ar2[0]],$tpl);
            echo "{$str2}\n";
        }
    }


}