<?php


namespace cli\mysql;

use hammer\cli\CmdBase;

ini_set('memory_limit', '2048M');


class CmdAnalyze extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    public function reformat()
    {

        $file = '/data/upload/test/sql.log';
        // $file = '/home/kinglone/sql.log';

        $f        = fopen($file, 'r');
        $fileLine = 0;
        $newStart = false;
        $sql      = '';
        $cur_date = 0;
        $s_date   = 2005212330;
        $e_date   = 2005220030;

        while (!feof($f))
        {
            $fileLine++;
            $str = trim(fgets($f));

            $r = preg_match('/^\#\d{6}\s\d{2}:\d{2}/', $str, $ar);
            if ($r)
            {
                echo "---------------------" . $ar[0] . "\n";
                $cur_date = intval(str_replace(array('#', ' ', ':'), '', $ar[0]));
                continue;
            }
            if ($cur_date > $s_date)
            {
                //$str = fgets($f);
                if ($newStart === false)
                {
                    $r = preg_match('/^(insert ignore into|insert into|update|delete from)/i', $str, $ar);
                    if ($r)
                    {
                        //  echo $str . "\n";
                        $newStart = true;
                        $sql      = $str . ' ';
                    }
                }
                else
                {
                    //'/*!*/;'
                    if (preg_match('/^\/\*\!\*\/;/i', $str, $ar))
                    {
                        $newStart = false;
                        //echo "\n-------------------------------------------\n";
                        echo strtolower(preg_replace('/(\s+)|(\t+)/', ' ', $sql));
                        echo "\n";
                        // echo "\n-------------------------------------------\n";
                    }
                    else
                    {
                        $sql .= $str . ' ';
                        //  echo "{$str}\n";
                    }
                }
                // echo $str . "\n";
            }
            if ($cur_date >= $e_date)
            {
                echo "\n end \n";
                break;
            }

        }
        fclose($f);
    }

    public function countTable()
    {
        $sql = "insert into fp_charge_record ( gateway_id, partner_id, charger, accepter, money, fee, coin, lcode, user_ip, gargs, change_log ) values ( 61, 5, 236100709, 236100709, 12.0, 0.00, 12.0, '611590076780968236100709', '183.202.204.178', '', '' )";

        $sql2 = "update fui_user_vod_3 set user_id = 237356262, fuuid = 'ba64f01cc6b7c5b7c4b7c6b7429e3f9512ee814013998ee660e111ff5dc245c2', api_code = 'fn5zmc4', mtype = 'media', site_id = -1, mid = 338349, eid = 1547209, user_ip = '61.186.25.67', is_clear = 0, vod_time = 1590076441, watch_time = 1166, app_code = 'aphone', update_time = null, create_time = 1590076441 where vod_id = 277645";
        $p    = '/\s?=\s?(\'?[\w,\d,\.]+\'?)\s?/';
        $p    = '/\s?=\s?(\'?[-,+,\w,\d,\.]+\'?)\s?/';
        // $p='/\s?=\s?(.*?)\s?,/';
        $p = '/\s?=\s?(.*?)\s?[,w,$]{1}/';
        $p = '/\s?=\s?(\'?[-,+,\w,\d,\.]+\'?)\s?/';


        /*
         * U-懒惰匹配
         * i-忽略英文字母大小写
         * x-忽略空白
         * s-让元字符' . '匹配包括换行符内所有字符
         */


        preg_match_all($p, $sql2, $ar);
        //  preg_match_all('/\s?=\s?((\'(,*)?\'|(\d+)))\s?,/',$sql2,$ar);

        $p_ins = '/\)\s?values\s?\((.*)?/';
        $p_upd = '/\s?=\s?(\'?[-,+,\w,\d,\.]+\'?)\s?/';
        //$file = '/data/upload/test/sql.log';
        $file     = '/home/kinglone/sql.log2';
        $f        = fopen($file, 'r');
        $fileLine = 0;
        $dels     = array();
        $updates  = array();
        $inserts  = array();
        $sqls     = array();
        while (!feof($f))
        {
            $fileLine++;
            if ($fileLine <= 0)
            {
                continue;
            }
            $str = trim(fgets($f));
            //$str="insert into fu_user_app_seq(id) values(null)";
            $r = preg_match_all('/^(insert ignore into|insert into|update|delete from)\s+\w+/', $str, $ar);
            if ($r)
            {
                $table = trim(str_replace($ar[1][0], '', $ar[0][0]));
                if (strstr($ar[1][0], 'insert'))
                {
                    $inserts[] = $table;
                    $str2      = preg_replace($p_ins, '', $str);
                    if ($str2 === $str)
                        die("\n{$str}\n");

                }
                else if (strstr($ar[1][0], 'update'))
                {
                    $updates[] = $table;
                    $sqls[]    = preg_replace($p_upd, ' ', $str);

                }
                else if (strstr($ar[1][0], 'delete'))
                {
                    $dels[] = $table;
                }
                else
                {
                    var_dump($str);
                }


            }
            else
            {
                if (!strstr($str, '--------------'))
                {
                    var_dump($str);
                }

            }
            //  break;


        }
        fclose($f);
        echo "\n";
        var_export($fileLine);
        echo "\n insert \n";
        $ar = array_count_values($inserts);
        arsort($ar);
        var_export($ar);
        echo "\n update \n";
        $ar = array_count_values($updates);
        arsort($ar);
        var_export($ar);
        echo "\n del\n";
        var_export(array_count_values($dels));


        echo "\n sql \n";
        $ar = array_count_values($sqls);
        arsort($ar);
        var_export($ar);

        echo "\n";
    }

    /*
            * U-懒惰匹配
            * i-忽略英文字母大小写
            * x-忽略空白
            * s-让元字符' . '匹配包括换行符内所有字符
            */
    public function pregSql()
    {
        $str = "insert into fu_user ( user_id ,platform_id ,account ,password ,disable ,create_time ,update_time )values( 237370270 ,11 ,'tv_00:e4:00:b4:cb:02' ,'3ce46f9577ad939' ,0 ,1590075070 ,1590075070 )";
        // $str = "insert into fp_charge_record ( gateway_id, partner_id, charger, accepter, money, fee, coin, lcode, user_ip, gargs, change_log ) values ( 61, 5, 236100709, 236100709, 12.0, 0.00, 12.0, '611590076780968236100709', '183.202.204.178', '', '' )";
        $p_ins = '/\)\s?values\s?\((.*)?/x';
        preg_match_all($p_ins, $str, $ar);
        // var_dump($ar);
        $str      = "update fu_user_mp set third_id=584371,user_id=236666413,mp_id=10,open_id='oh4lf1zwulq4fsgs6f_xc8h80ako',subscribe=0,subscribe_time=null,unsubscribe_time=null,subscribe_info=null,h5_info='{\"openid\":\"oh4lf1zwulq4fsgs6f_xc8h80ako\",\"nickname\":\"回忆只会让我更悲伤\",\"sex\":1,\"language\":\"zh_cn\",\"city\":\"handan\",\"province\":\"hebei\",\"country\":\"cn\",\"headimgurl\":\"http:\\/\\/thirdwx.qlogo.cn\\/mmopen\\/vi_32\\/q0j4twgtftidwrupexicod5azruvhprqa7nmbdbhqewuhjgz6fosffuldokaeos93mj6uenesxjxhukap2ibwl9g\\/132\",\"privilege\":[],\"unionid\":\"opawwsybpepfapcoasjuhiepuhnm\"}',update_time=now() where um_id=2597808";
        $p_update = '/\s?=\s?(\'?[\w,\d,\.]+\'?)\s?/';
        $p_update = '/(\s?=\s?(\'?[\w,\d,\.]+\'?)\s?)/';
        $p_update = '/(\s?=\s?\'?[\w,\d,\.]+\'?\s?)/';

        preg_match_all($p_update, $str, $ar);
        echo "\n{$str}\n";
        var_dump($ar);

        echo "\n";
        echo preg_replace($p_update, ' ', $str);
        echo "\n";
    }


    public function convSqlToModel($str, &$action, &$table, &$words, &$parts, &$conditions)
    {
        $str        = trim(strtolower(preg_replace('/\s+/', ' ', $str)));
        $str        = preg_replace('/`/', '', $str);
        $str        = preg_replace('/,\s/', ',', $str);
        $str        = preg_replace('/ in /', '<>', $str);
        $action     = substr($str, 0, 6);
        $str        = trim(preg_replace('/^\s?(insert ignore into|insert into|update|delete from)/', '', $str));
        $words      = [];
        $parts      = explode(' ', $str);
        $conditions = [];//条件  conditions
        //先取出关键信息，拿出去做统一分析取出表内直接或间接的被操作表
        if ($action == 'insert' && strstr($str, ') values ('))
        {
            preg_match_all('/\((.*)?\) values \((.*)?\)/', $str, $ar);
            if (isset($ar[1][0]) && $ar[1][0] && isset($ar[2][0]) && $ar[2][0])
            {
                $ks = explode(',', $ar[1][0]);
                $vs = explode(',', $ar[2][0]);
                if (count($ks) === count($vs))
                    foreach ($ks as $key => $k)
                    {
                        $k = trim($k);
                        if (!isset($words[$k]))
                            $words[$k] = [];
                        $words[$k][] = trim($vs[$key]);
                    }
            }
        }
        else
        {
            $isCondStar = false;
            foreach ($parts as $part)
            {
                if ($isCondStar)
                    $conditions[] = $part;
                $sets = explode(',', $part);
                foreach ($sets as $set)
                {
                    if (strstr($str, '<>'))
                    {
                        $ar2 = explode('<>', $set);
                        if (count($ar2) == 2)
                        {
                            if (!isset($words[$ar2[0]]))
                                $words[$ar2[0]] = [];
                            $words[$ar2[0]] = array_merge($words[$ar2[0]], explode(',', preg_replace('/\(|\)/', '', $ar2[1])));
                        }
                    }
                    else
                    {
                        $ar2 = explode('=', $set);
                        //var_dump($ar2);
                        if (count($ar2) == 2)
                        {
                            if (!isset($words[$ar2[0]]))
                                $words[$ar2[0]] = [];
                            $words[$ar2[0]][] = $ar2[1];
                        }
                    }

                }
                if ($part == 'where')
                    $isCondStar = true;
            }

        }
        $table = $parts[0];
    }

    public function analyzeSqlStr($sqlStr)
    {
        if ($this->isTarget($sqlStr))
        {
            echo "$sqlStr\n";
            $action     = '';
            $table      = '';
            $words      = [];
            $parts      = [];
            $conditions = [];
            $this->convSqlToModel($sqlStr, $acion, $table, $words, $parts, $conditions);
            echo ">>>>>\taction:$action table:$table\n\n";
            foreach ($this->relations[$table] as $modelName => $relat)
            {
                echo "\nmodel name $modelName,";
                if (isset($relat['srcKey']))
                {
                    $srcKeys = [];
                    if (isset($words[$relat['srcKey']]))
                    {
                        foreach ($words[$relat['srcKey']] as $v)
                            $srcKeys[] = $v;
                    }
                    else if (count($conditions))
                    {
                        $rows = $this->db->setText('select `' . $relat['srcKey'] . '` from ' . $table . ' where ' . str_replace('<>', ' in ', join(' ', $conditions)))->queryAll();
                        foreach ($rows as $row)
                            $srcKeys[] = $row[$relat['srcKey']];
                    }
                    if ($srcKeys)
                    {
                        echo "\n src keys " . join(',', $srcKeys);
                        $rows = $this->db->setText('select `' . $relat['key'] . '` from `' . $relat['table'] . '` where `' . $relat['distKey'] . '` in ("' . join('","', $srcKeys) . '")')->queryAll();
                        if ($rows)
                        {
                            echo "\n model key:";
                            foreach ($rows as $row)
                            {
                                $this->modelInfos[$modelName][] = $row[$relat['key']];
                                echo $row[$relat['key']] . ',';
                            }
                        }
                    }
                }
                else
                {
                    echo "\n model key:";
                    if (isset($words[$relat['key']]))
                    {
                        foreach ($words[$relat['key']] as $v)
                        {
                            $this->modelInfos[$modelName][] = $v;
                            echo $v . ',';
                        }
                    }
                    else if (count($conditions))
                    {
                        $rows = $this->db->setText('select `' . $relat['key'] . '` from ' . $table . ' where ' . str_replace('<>', ' in ', join(' ', $conditions)))->queryAll();
                        foreach ($rows as $row)
                        {
                            $this->modelInfos[$modelName][] = $row[$relat['key']];
                            echo $row[$relat['key']] . ',';
                        }
                    }
                }
                echo "\n";
            }
            return true;
        }
        else
        {
            //   var_dump($sqlStr);
            return false;
        }

    }

    public function getBinlogs()
    {
        $files   = explode(',', '037786,037787,037788,037789,037790,037791,037792,037793,037794,037795,037796,037797,037798,037799,037800,037801,037802,037803,037804,037805,037806,037807,037808,037809,037810');
        $dir     = "/ext/tmp";
        $get_bin = "scp -P5044 -i /home/kinglone/.ssh/id_dsa -o PubkeyAcceptedKeyTypes=+ssh-dss yangjl@192.168.8.100:/usr/mysqlbin/mysql-bin.{i} {bin}";


        $files_cnt  = count($files);
        $file_index = 0;
        foreach ($files as $file_fix)
        {
            $file_index++;
            $date     = date('Y-m-d H:i:s', time());
            $file_bin = "{$dir}/bin.{$file_index}";
            $file_sql = "{$dir}/sql.{$file_index}";
            $ret_sql  = "{$dir}/json.{$file_index}";

            echo "{$file_index}/{$files_cnt}\t{$file_bin}\t{$file_sql} {$date}\n";
            //获取bin
            if (is_file($file_bin) || is_file($ret_sql))
            {
                echo "\n bin exist\n";
            }
            else
            {
                $cmd = str_replace(array('{i}', '{bin}'), array($file_fix, $file_bin), $get_bin);
                echo "\n bin not exist:\n{$cmd}\n";
                exec($cmd, $ar);
                var_dump($ar);
                $date = date('Y-m-d H:i:s', time());
                echo "got :{$date}\n";
            }
        }
    }

    public function bin2sql()
    {
        $files = explode(',', '037786,037787,037788,037789,037790,037791,037792,037793,037794,037795,037796,037797,037798,037799,037800,037801,037802,037803,037804,037805,037806,037807,037808,037809,037810');
        $dir   = "/ext/tmp";
        //$bin_txt    = "mysqlbinlog  --base64-output=DECODE-ROWS {$dir}/bin.{i} {$dir}/txt.{i}";
        $bin_txt = "mysqlbinlog  --base64-output=DECODE-ROWS {bin} > {sql}";

        $files_cnt  = count($files);
        $file_index = 0;
        foreach ($files as $file_fix)
        {
            $file_index++;
            $date     = date('Y-m-d H:i:s', time());
            $file_bin = "{$dir}/bin.{$file_index}";
            $file_sql = "{$dir}/sql.{$file_index}";

            if (is_file($file_sql))
            {
                echo "\nskip  <<<SKIP>>>>\n";
                continue;
            }
            echo "{$file_index}\t{$file_bin}\t{$file_sql} {$date}\n";
            //获取bin
            for ($i = 0; $i < 60; $i++)
            {
                echo "\n wait {$i}";
                if (is_file($file_bin))
                {
                    break;
                }
                else
                {
                    echo "\n {$i}*5: wait bin \n";
                    sleep(5);
                }

            }


            if (is_file($file_sql))
            {
                echo "\n sql exist <<<SKIP>>>>\n";
            }
            else if (!is_file($file_bin))
            {
                echo "\n bin not exist\n";
                die;
            }
            else
            {
                // mysqlbinlog  --base64-output=DECODE-ROWS /data/upload/test/mysql-bin.004873 /data/upload/test/sql.log
                $cmd = str_replace(array('{bin}', '{sql}'), array($file_bin, $file_sql), $bin_txt);
                echo "\n sql not exist:\n{$cmd}\n";
                exec($cmd, $ar);
                var_dump($ar);
                $date = date('Y-m-d H:i:s', time());
                echo "conved :{$date}\n";
            }
        }

    }

    public function countOp()
    {
        $is_force   = $this->inputBox->tryGetString('force') === 'yes';
        $dir        = "/ext/tmp";
        $file_index = 0;
        for ($i = 1; $i <= 24; $i++)
        {
            $file_index++;
            $date      = date('Y-m-d H:i:s', time());
            $file_sql  = "{$dir}/sql.{$file_index}";
            $file_json = "{$dir}/json.{$file_index}";
            if ($is_force === false && is_file($file_json))
            {
                echo "\nskip   <<<SKIP>>>>\n";
                continue;
            }
            echo "{$file_index} \t{$file_sql} => {$file_json} {$date}\n";
            //获取bin
            for ($i = 0; $i < 60; $i++)
            {
                echo "\n wait {$i}";
                if (is_file($file_sql))
                {
                    break;
                }
                else
                {
                    echo "\n {$i}*5: wait sql \n";
                    sleep(5);
                }
            }
            if (is_file($file_sql))
            {
                echo "\n go on ! \n";
            }
            else
            {
                echo "\n sql not exist !!!!!!!!!!!!!!!!!!!\n";
                die;
            }
            echo "\n count\n";
            $ret = $this->count_op($file_sql);
            //
            file_put_contents($file_json, json_encode($ret));
            $date = date('Y-m-d H:i:s', time());
            echo "count :{$date}\n";

        }
    }

    /**
     * @param $file
     * @return array
     */
    private function count_op($file)
    {
        $ops      = array(
            'select' => array(),
            'update' => array(),
            'insert' => array(),
            'delete' => array(),
            'SELECT' => array(),
            'UPDATE' => array(),
            'INSERT' => array(),
            'DELETE' => array()
        );
        $op_keys  = array_keys($ops);
        $f        = fopen($file, 'r');
        $fileLine = 0;
        $cur_date = 0;
        while (!feof($f))
        {
            $fileLine++;
            $str = trim(fgets($f));

            $r = preg_match('/^\#\d{6}\s+\d{1,2}:\d{1,2}:\d{1,2}/', $str, $ar);
            //  $r = preg_match('/^\#\d{6}/', $str, $ar);

            if ($r)
            {
                //  echo "---------------------" . $ar[0];
                $cur_date = str_replace(array('#'), '', $ar[0]);
                //   echo "---------------------" . $cur_date . "\n";
                //var_dump($cur_date);
                continue;
            }


            $r = preg_match('/^(insert|update|delete) /i', $str, $ar);
            if ($r && count($ar) === 2)
            {
                if (count($ar) === 2)
                {
                    $ops[$ar[1]][] = $cur_date;
                }
                else
                {
                    var_dump($ar);
                    die;
                }
            }
        }
        fclose($f);
        var_dump($ops);
        foreach ($ops as $op => $array)
        {
            $cnt = count($array);
            echo "{$op}:{$cnt}\n";
            if (!in_array($op, $op_keys))
            {
                echo "unset {$op}\n";
                unset($ops[$op]);
            }
        }
        return $ops;
    }

    /**
     * 给chart.js用的json
     */
    public function convData()
    {
        $start = 1;
        $end   = 1;
        $dir   = "/ext/tmp";
        $opts  = array('select' => array(), 'insert' => array(), 'update' => array(), 'delete' => array(), 'all' => array());
        for ($i = $start; $i <= $end; $i++)
        {
            $data = json_decode(file_get_contents("{$dir}/json.{$i}"), true);
            var_dump($data);
            foreach ($data as $key => $array)
            {
                $key         = strtolower($key);
                $opts[$key]  = array_merge($opts[$key], $array);
                $opts['all'] = array_merge($opts['all'], $array);
            }
        }
        echo "conv\n";
        $uniques = array_values(array_unique($opts['all']));
        sort($uniques);

        $uniques_vals = array_fill_keys($uniques, 0);
        var_dump($uniques_vals);
        foreach ($opts as $key => $array)
        {
            $opts[$key] = array_values(array_merge($uniques_vals, array_count_values($array)));
            //    krsort($opts[$key]);

        }
        //var_dump($opts);
        $opts['labels'] = $uniques;
        var_dump($opts);
        $dir = '/data/code/porter/app/static/data';
        file_put_contents("{$dir}/test.json", json_encode($opts));
    }

    public function getUnique($date)
    {

    }
    //6574400,1  9491017 ~ 9590217
    //  /hammer_porter mysql/analyze countSql --env=dev0 --log=sql.1 --start='200607 00:04:47' --end='200607 00:05:03' --range='9491017~9590217'
    /**
     * 对sql 进行统计
     * 会移除 sql中的数字 被引号 引起来的字符串
     */
    public function countSql()
    {


        $file_name = $this->inputBox->getNotEmptyString('log');
        $start     = $this->inputBox->getNotEmptyString('start');
        $end       = $this->inputBox->getNotEmptyString('end');
        $range     = $this->inputBox->tryGetString('range');//3163673~3262873
        $s_line    = 0;
        $e_line    = 0;
        if ($range)
        {
            list($s_line, $e_line) = explode('~', $range);
        }
        $dir       = "/ext/tmp";
        $f         = fopen("{$dir}/{$file_name}", 'r');
        $fileLine  = 0;
        $newStart  = false;
        $sql       = '';
        $cur_date  = 0;
        $s_date    = intval(str_replace(array(' 0:', '#', ' ', ':'), array(' 00:', '', '', ''), $start));
        $e_date    = intval(str_replace(array(' 0:', '#', ' ', ':'), array(' 00:', '', '', ''), $end));
        $startLine = 0;
        $endLine   = 0;
        $sqls      = array();
        while (!feof($f))
        {
            $fileLine++;
            if ($s_line)
            {
                if ($s_line > $fileLine)
                {
                    continue;
                }
                else if ($e_line < $fileLine)
                {
                    //    break;
                }
            }
            $str = trim(fgets($f));
            $r   = preg_match('/^\#\d{6}\s+\d{1,2}:\d{1,2}:\d{1,2}/', $str, $ar);
            //  $r = preg_match('/^\#\d{6}/', $str, $ar);

            if ($r)
            {
                //     echo "---------------------" . $ar[0];
                $cur_date = intval(str_replace(array(' 0:', '#', ' ', ':'), array(' 00:', '', '', ''), $ar[0]));
                //     echo "------ {$fileLine} ---------------" . $cur_date . "\n";

                //var_dump($cur_date);
                continue;
            }

            if ($cur_date > $s_date)
            {
                if ($startLine === 0)
                {
                    $startLine = $fileLine;
                }
                $str = fgets($f);
                if ($newStart === false)
                {
                    $r = preg_match('/^(insert ignore into\s+\w+\s|insert into\s+\w+\s|update\s+\w+\s|delete from\s+\w+\s)/i', $str, $ar);
                    if ($r)
                    {
                        echo $str . "\n";
                        $newStart = true;
                        $sql      = $str . ' ';
                        var_dump($ar);
                        echo "<<< new :{$str}\n";
                    }
                }
                else
                {
                    //'/*!*/;'
                    if (strstr($str, '/*!*/;') || strstr($str, '# at'))
                    {
                        echo "{$str} :end>>>\n";
                        $newStart = false;
                        echo "\n-------------------------------------------\n";
                        $sql  = strtolower(preg_replace('/(\s+)|(\t+)/', ' ', $sql));
                        $sql_ = preg_replace('/\'(.*?)\'/i', ' ', preg_replace('/\s+/i', ' ', preg_replace('/\d,?/i', ' ', $sql)));
                        echo "{$sql}\n{$sql_}\n";
                        echo "\n|||||||||||||||||||||||||||||||||||||||||||\n";
                        $sqls[] = $sql_;
                    }
                    else
                    {
                        $sql .= $str . ' ';
                    }
                }
            }
            if ($cur_date >= $e_date)
            {
                $endLine = $fileLine;
                echo "\n end  {$cur_date} >= {$e_date}  {$startLine} ~ {$endLine}\n";
                break;
            }

        }
        fclose($f);
        $cnts = array_count_values($sqls);
        arsort($cnts);
        var_dump($cnts);
        return $cnts;

    }


}