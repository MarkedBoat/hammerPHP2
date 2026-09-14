<?php


namespace cli\mysql;
ini_set('memory_limit', '2048M');

use hammer\cli\CmdBase;
use hammer\sys\Sys;


class CmdFcg extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    public function repeat()
    {
        $sql      = "update fr_virtual_repository_030 set update_time=now()  WHERE api_id=230 and video_id={id}";
        $file     = Sys::app()->params['cli']['codeUpload'] . '/20200611.csv';
        die("\nend\n");
        $f        = fopen($file, 'r');
        $fileLine = 0;
        $ids      = array();
        $db       = Sys::app()->db('fcg_master');
        while (!feof($f))
        {
            $fileLine++;
            $str = trim(fgets($f));
            if ($str)
            {
                $ids[] = $str;
            }
        }
        fclose($f);
        $size      = 40;
        $ids_array = array_chunk($ids, $size);
        unset($ids);
        $str = str_repeat('.', 60);
        $cnt = count($ids_array);
        foreach ($ids_array as $i => $ids)
        {
            $sqls = array();
            foreach ($ids as $id)
            {
                $sqls[] = str_replace('{id}', $id, $sql);
            }
            $start = $i * $size;
            $end   = $start + $size;
            //echo "\n" . join(',', $ids) . "\n";
            $r = $db->setText(join(';', $sqls))->execute();
            echo "{$str}{$r} {$i}/{$cnt} {$start}~{$end}\n";
            for ($i = 0; $i < 60; $i++)
            {
                echo '.';
                sleep(1);
            }
            echo "\n";
        }
    }
}