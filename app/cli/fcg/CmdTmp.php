<?php


namespace cli\fcg;

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

    ///porter_hammer fcg/tmp infohash_disable --env=poseidon_test --input_file=/home/yangl_upload/fail.txt
    // /porter_hammer fcg/tmp infohash_disable --env=poseidon_test --nginx_log=/home/yangl_upload/papi_hash_error_20200426.log --upload_files=/var/log/porter/ids_retry_61.csv
    public function infohash_disable()
    {
        $input_file   = $this->inputBox->tryGetString('input_file');
        $out_file     = $this->inputBox->tryGetString('out_file');
        $api_ids      = $this->inputBox->tryGetString('api_ids');
        $upalod_files = $this->inputBox->tryGetString('upload_files');
        $nginx_log    = $this->inputBox->tryGetString('nginx_log');//是否是nginx日志
        if (!$input_file && !$nginx_log)
            die("\ninput_file:csv nginx_log:log 不能同时为空\n");
        $api_ids      = strlen($api_ids) ? explode(',', $api_ids) : [];
        $upalod_files = strlen($upalod_files) ? explode(',', $upalod_files) : [];
        $sql_file     = Sys::app()->params['cli']['logDir'] . '/' . ($out_file ? $out_file : 'hashs_disable.sql');

        echo "\nstart\n";
        $hashs = [];
        if ($input_file)
        {
            $f        = fopen($input_file, 'r');
            $fileLine = 0;
            while (!feof($f))
            {
                $fileLine++;
                $hashs[trim(fgets($f))] = 0;
            }
            fclose($f);
        }
        else
        {
            $p        = '/infohash=(\w+)&/';
            $f        = fopen($nginx_log, 'r');
            $fileLine = 0;
            while (!feof($f))
            {
                $fileLine++;
                $str = fgets($f);
                preg_match($p, $str, $ar);
                if (count($ar) === 2)
                {
                    $hashs[trim($ar[1])] = 0;
                }
            }
            fclose($f);
        }
        $db             = Sys::app()->db('db_fcg_repository');
        $video_tables   = [];
        $video_ids      = [];
        $video_id_hashs = [];
        for ($i = 0; $i < 20; $i++)
        {
            $video_tables[] = "fr_video_play_" . str_pad($i, 3, '0', STR_PAD_LEFT);
        }
        echo date('Y-m-d H:i:s', time()) . ":get video id\n";
        $i          = 0;
        $i_cnt      = count($hashs);
        $hashs_file = Sys::app()->params['cli']['logDir'] . '/hashinfos.csv';
        file_put_contents($hashs_file, '');
        foreach ($hashs as $hash => $tmp)
        {
            $i++;
            file_put_contents($hashs_file, "{$hash}\n", FILE_APPEND);
            foreach ($video_tables as $j => $video_table)
            {
                echo date('Y-m-d H:i:s', time()) . ":get video id {$i}/{$i_cnt} {$j}/20\n";

                $r = $db->setText("select video_id from {$video_table} where infohash=:infohash limit 1")->bindArray([
                    ':infohash' => $hash
                ])->queryScalar();
                if ($r)
                {
                    $id                  = intval($r);
                    $hashs[$hash]        = ['video_id' => $id, 'mods' => []];
                    $video_ids[]         = $id;
                    $video_id_hashs[$id] = $hash;
                    break;
                }
            }
        }

        var_dump([$hashs, $video_ids]);
        file_put_contents(Sys::app()->params['cli']['logDir'] . '/hashs_info.json', json_encode([$hashs, $video_ids]));

        $virtual_tables = [];
        for ($i = 0; $i < 100; $i++)
        {
            $virtual_tables[] = "fr_virtual_repository_" . str_pad($i, 3, '0', STR_PAD_LEFT);
        }
        file_put_contents($sql_file, '');

        $video_ids_array = array_chunk($video_ids, 50);
        $array_cnt       = count($video_ids_array);
        $sql_cnt         = 0;
        $api_ids_count   = [];
        $api_id_filter   = count($api_ids) ? true : false;

        foreach ($virtual_tables as $i => $virtual_table)
        {
            foreach ($video_ids_array as $j => $ids)
            {
                $date = date('Y-m-d H:i:s', time());
                echo $date . ":get sql table:{$i}/100  ids_chunk:{$j}/{$array_cnt}\n";
                $ids_str = join(',', $ids);
                $table   = $db->setText("select video_id,api_id from {$virtual_table} where video_id in ($ids_str)")->queryAll();
                foreach ($table as $row)
                {

                    $api_id_row = intval($row['api_id']);
                    if ($api_id_filter && !in_array($api_id_row, $api_ids))
                        continue;
                    if (!isset($api_ids_count[$api_id_row]))
                        $api_ids_count[$api_id_row] = [];
                    $sql = "UPDATE `db_fcg_repository`.`{$virtual_table}` SET `disable`='1',`update_time`=NOW() WHERE `video_id`='{$row['video_id']}' and`api_id`='{$row['api_id']}';\n";
                    echo "{$sql_cnt}:{$sql}";
                    $sql_cnt++;
                    file_put_contents($sql_file, $sql, FILE_APPEND);
                    $api_ids_count[$api_id_row][]                               = $row['video_id'];
                    $hashs[$video_id_hashs[intval($row['video_id'])]]['mods'][] = [$i, $row['api_id']];
                }

            }
        }

        foreach ($api_ids_count as $app_id => $video_ids)
        {
            file_put_contents(Sys::app()->params['cli']['logDir'] . '/ids_retry_' . $app_id . '.csv', join("\n", array_unique($video_ids)));
        }

        //var_dump($db->setText('show tables;')->queryAll());
        echo "\n\n{$sql_file}\n";
        $upalod_files[] = $sql_file;
        foreach ($upalod_files as $upalod_file)
        {
            $text = Curl::upToContent($upalod_file);
            var_export(json_decode($text, true));
            echo "\nuploaded:{$upalod_file}\n";
        }

        //echo json_encode($hashs,JSON_PRETTY_PRINT);
        //echo json_encode($hashs);
        echo "\n";

    }

    public function redis()
    {
        $array = [
            //'ali' => ['host' => 'r-2zee8444844418a4.redis.rds.aliyuncs.com', 'port' => 6379, 'password' => 'funshion123!@#'],
            'bin' => ['host' => 'redis-node02', 'port' => 6389, 'password' => 'Ab-18upTxsmuzsf'],
            'pay' => ['host' => 'redis-node03', 'port' => 6389, 'password' => 'on4PshJqmibi^2n'],
            'mpr' => ['host' => 'redis_mpr', 'port' => 6389, 'password' => 'Cipa6hd0ev^vkCh'],
        ];
        $redis = new \Redis();
        //   $redis->connect('redis-node02', 6389);
        //   $redis->auth('Ab-18upTxsmuzsf');
        //   $redis->select(10);
        /*
        $r=$redis->info();
        echo "\n" . json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        var_dump($r);
        var_dump($redis->dbSize());
        die;*/
        $fileName = '/data/git-webroot/api-htdocs/Bftvapi/download/log.csv';
        file_put_contents($fileName, '');
        $i    = 0;
        $data = [];
        foreach ($array as $redisName => $cfg)
        {
            $data[$redisName] = ['dbs' => []];
            echo "redis:{$redisName}\n";
            $countRedis = 0;
            $redis      = new \Redis();
            $redis->connect($cfg['host'], $cfg['port']);
            $redis->auth($cfg['password']);
            $info = $redis->info();
            // var_export($info);die;
            $dbIndexs = [];
            foreach ($info as $key => $str)
            {
                if (substr($key, 0, 2) === 'db' && substr($str, 0, 5) === 'keys=')
                {
                    $dbIndexStr = substr($key, 2);
                    $dbIndex    = intval($dbIndexStr);
                    if ($dbIndexStr === strval($dbIndex))
                    {
                        $dbIndexs[] = $dbIndex;
                        echo "{$key}:{$str}\n";
                        $data[$redisName]['dbs'][$key] = $str;
                    }
                }
            }
            echo "\n";
            //echo "\n" . json_encode($redis->info(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            // continue;
            foreach ($dbIndexs as $db)
            {
                echo "redis:{$redisName}/db:{$db}\n";
                $redis->select($db);
                $iterator = null;
                $countDb  = 0;
                $goon     = true;
                while ($goon)
                {
                    $keys = $redis->scan($iterator);
                    if ($keys === false)
                    {//迭代结束，未找到匹配pattern的key
                        $goon = false;
                    }
                    if (is_array($keys))
                        foreach ($keys as $key)
                        {
                            echo "all:{$i}\ttime:" . date('Y-m-d H:i:s', time()) . "\tredis:{$redisName}\tcount:{$countRedis}\tdb:{$db}\tcount:{$countDb}\tkey>{$key}\n";
                            $i++;
                            $countRedis++;
                            $countDb++;
                            file_put_contents($fileName, "{$i},{$redisName},{$db},{$key}\n", FILE_APPEND);

                        }
                }
            }
            $data[$redisName]['total'] = $countRedis;
        }


        echo "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "\nover\n";
        die;
    }


}