<?php


namespace cli\mysql;

use hammer\cli\CmdBase;
use hammer\db\MysqlPdo;
use hammer\sys\Sys;


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

    /**
     * 导出连过网的
     */
    public function slave_to_funtv()
    {
        $selectedTable = $this->inputBox->tryGetString('tn');

        $dbBf       = Sys::app()->db('cli_bftv_slave');
        $dbFuntv    = Sys::app()->db('funtv');
        $tnsAllData = [
            'std_album',
            'std_episode_qiyi',
            'bftv_copyrights_request_statis',
            'std_album_keywords',
            'std_l_channel_video',
            'bftv_order',
            'std_uuid_getuiid',
            'std_tv_short_video_baofeng',
            'bftv_copyright_request'
        ];
        $tnsSkip    = ['std_user_3rd', 'std_voice_pcklist'];
        $tableTns   = $dbBf->setText("SELECT sum(DATA_LENGTH+INDEX_LENGTH)/1024/1024 'size',`table_name` AS tn FROM INFORMATION_SCHEMA. tables WHERE table_schema = 'baofeng_tv'GROUP BY `table_name`;")->queryAll();
        if ($selectedTable)
            $tableTns = [['size' => 100, 'tn' => $selectedTable]];
        foreach ($tableTns as $i => $row)
        {
            $tn = $row['tn'];
            echo "{$i}/{$tn}\n";
            if (in_array($tn, $tnsSkip, true))
                continue;
            if ($this->getStatus($tn, 'create') === false)
            {
                echo "not created\n";
                $row2 = $dbBf->setText("show create table {$tn};")->queryRow();
                if (isset($row2['View']))
                    continue;
                $sql = str_replace(['USING BTREE', 'utf8mb4_unicode_ci', 'utf8mb4'], [
                    '',
                    'utf8_general_ci',
                    'utf8'
                ], $row2['Create Table']);

                $ars = explode("\n", $sql);
                foreach ($ars as $index => $ele)
                {
                    $ele  = trim($ele);
                    $last = substr($ele, -1);
                    if (strstr($ele, 'KEY') && (strstr($ele, '`,`') || strstr($ele, '(`')))
                    {
                        if (strstr($ele, 'COMMENT'))
                        {
                            $ars[$index] = explode('COMMENT', $ele)[0] . ($last === ',' ? ',' : '');
                        }
                    }
                }
                $sql = join("\n", $ars);
                $dbFuntv->setText($sql)->execute();
                $this->logStatus($tn, 'create', 'ok');
            }
            else
            {
                echo "has created\n";
            }

            $limit = 0;
            if (!in_array($tn, $tnsAllData, true) && intval($row['size']) > 500)
            {
                $limit = 10000;
            }
            $this->logStatus($tn, 'rowsLimit', $limit);
            if ($this->getStatus($tn, 'coypData1') === false)
            {
                echo "not coypData\n";

                $tableInfo = $dbBf->setText("show full columns from {$tn};")->queryAll();
                $isPkInt   = false;
                $pk        = '';
                $fields    = [];
                $fields2   = [];
                $fields3   = [];
                foreach ($tableInfo as $row2)
                {
                    if ($isPkInt === false && $row2['Key'] === 'PRI' && strstr($row2['Type'], 'int('))
                    {
                        $pk      = $row2['Field'];
                        $isPkInt = true;
                    }
                    $fields[]  = "`{$row2['Field']}`";
                    $fields2[] = "`{$row2['Field']}`=:{$row2['Field']}_{index}";
                    $fields3[] = $row2['Field'];
                }
                if ($isPkInt === false)
                {
                    $this->logStatus($tn, 'coypData1', 'noIntPk');
                    continue;
                }

                $maxPkVal = $this->getStatus($tn, 'maxId');
                if (empty($maxPkVal))
                {
                    $maxPkVal = $dbBf->setText("select max(`{$pk}`) from {$tn}")->queryScalar();
                    $this->logStatus($tn, 'maxId', $maxPkVal);
                }
                $maxPkVal    = intval($maxPkVal);
                $goon        = true;
                $selection   = join(',', $fields);
                $sqlSelctTpl = "select {$selection} from {$tn} where `{$pk}`<{maxVal} order by `{$pk}` desc limit 100 ";
                $sqlInsTpl   = "insert ignore into {$tn} set " . join(',', $fields2);

                $insCount = 0;
                while ($goon)
                {
                    $table2 = $dbBf->setText(str_replace('{maxVal}', $maxPkVal, $sqlSelctTpl))->queryAll();
                    if (is_array($table2) && count($table2))
                    {
                        $sqls = [];
                        $bind = [];
                        foreach ($table2 as $index => $ele)
                        {
                            $sqls[] = str_replace('{index}', $index, $sqlInsTpl);
                            foreach ($fields3 as $field)
                            {
                                $bind["{$field}_{$index}"] = $ele[$field];
                            }
                            $maxPkVal = $ele[$pk];
                            $insCount++;
                        }
                        $this->logStatus($tn, 'maxId', $maxPkVal);
                        $dbFuntv->setText(join(';', $sqls))->bindArray($bind)->execute();
                        $sqlCount = count($sqls);
                        if ($limit && $insCount >= $limit)
                        {
                            $goon = false;
                        }
                        if ($sqlCount < 100)
                        {
                            $goon = false;
                        }

                    }
                    else
                    {
                        $goon = false;
                    }
                    echo date('Y-m-d H:i:s', time()) . "#table index:{$i}/{$tn} --maxPk:{$maxPkVal} insertCount:{$insCount} table limit:{$limit}\n";
                }
                $this->logStatus($tn, 'coypData1', 'ok');


                // $dbFuntv->setText($sql)->execute();
                // $this->logStatus($tn, 'coypData', 'ok');
            }
            else
            {
                echo "has coypData\n";
            }

        }
    }


    public function logStatus($tn, $key, $status)
    {
        $tnFile = "/var/log/porter/mysql/status/{$tn}.txt";
        $val    = $this->getStatus($tn, $key);

        if ($val === false)
        {
            file_put_contents($tnFile, "\n<{$key}:$status", FILE_APPEND);
        }
        else
        {
            file_put_contents($tnFile, str_replace("<{$key}:{$val}", "<{$key}:$status", file_get_contents($tnFile)));
        }
    }

    public function getStatus($tn, $key)
    {
        $tnFile = "/var/log/porter/mysql/status/{$tn}.txt";
        if (!file_exists($tnFile))
            file_put_contents($tnFile, '');
        $str = strstr(file_get_contents($tnFile), "<{$key}:");
        if ($str)
        {
            return str_replace("<{$key}:", '', explode("\n", $str)[0]);
        }
        else
        {
            return false;
        }
    }


    public function get_src_sql()
    {

    }

    public function src_to_funtv()
    {
        $dbInfos = [
            ['baofeng_source_31', Sys::app()->db('src0'), Sys::app()->db('to0')],
            ['baofeng_source_41', Sys::app()->db('src1'), Sys::app()->db('to1')]
        ];
        foreach ($dbInfos as $dbInfo)
        {
            $dbSrc    = $dbInfo[1];
            $dbTo     = $dbInfo[2];
            $tableTns = $dbSrc->setText("SELECT `table_name` AS tn FROM INFORMATION_SCHEMA. tables WHERE table_schema = '{$dbInfo[0]}';")->queryAll();
            foreach ($tableTns as $i => $row)
            {
                $tn = $row['tn'];
                echo "{$i}/{$tn}\n";

                $row2 = $dbSrc->setText("show create table {$tn};")->queryRow();
                if (isset($row2['View']))
                    continue;
                $sql = str_replace(['USING BTREE', 'utf8mb4_unicode_ci', 'utf8mb4'], [
                    '',
                    'utf8_general_ci',
                    'utf8'
                ], $row2['Create Table']);

                $ars = explode("\n", $sql);
                foreach ($ars as $index => $ele)
                {
                    $ele  = trim($ele);
                    $last = substr($ele, -1);
                    if (strstr($ele, 'KEY') && (strstr($ele, '`,`') || strstr($ele, '(`')))
                    {
                        if (strstr($ele, 'COMMENT'))
                        {
                            $ars[$index] = explode('COMMENT', $ele)[0] . ($last === ',' ? ',' : '');
                        }
                    }
                }
                $sql = join("\n", $ars);
                try
                {
                    $dbTo->setText($sql)->execute();
                } catch (\Exception $e)
                {

                }


            }
        }


    }


    public function src_to_local()
    {
        $dbUq     = $this->inputBox->getNotEmptyString('db');
        $dbSys    = Sys::app()->db('dev0');
        $rowLocal = $dbSys->setText("select id,db_uq, db_name, db_host, db_port, db_un, db_psw, db_charset, create_at, update_at, last_try, db_title, db_remark from dev_db_sync_config where db_uq=:db_uq limit 1")->bindArray([':db_uq' => 'local'])->queryRow();
        $rowSrc   = $dbSys->setText("select id,db_uq, db_name, db_host, db_port, db_un, db_psw, db_charset, create_at, update_at, last_try, db_title, db_remark from dev_db_sync_config where db_uq=:db_uq limit 1")->bindArray([':db_uq' => $dbUq])->queryRow();
        if (empty($rowLocal) || empty($rowSrc))
            Sys::app()->interruption()->outError('信息错误');
        $dbSrc   = MysqlPdo::configDb([
            'connectionString' => "mysql:host={$rowSrc['db_host']};port={$rowSrc['db_port']};dbname={$rowSrc['db_name']}",
            'username'         => $rowSrc['db_un'],
            'password'         => $rowSrc['db_psw'],
            'charset'          => $rowSrc['db_charset'],
            'readOnly'         => true,
        ]);
        $dbLocal = MysqlPdo::configDb([
            'connectionString' => "mysql:host={$rowLocal['db_host']};port={$rowLocal['db_port']};dbname={$rowSrc['db_name']}",
            'username'         => $rowLocal['db_un'],
            'password'         => $rowLocal['db_psw'],
            'charset'          => $rowLocal['db_charset'],
            'readOnly'         => true,
        ]);

        $tnsAllData   = [
            'std_album',
            'std_episode_qiyi',
            'bftv_copyrights_request_statis',
            'std_album_keywords',
            'std_l_channel_video',
            'bftv_order',
            'std_uuid_getuiid',
            'std_tv_short_video_baofeng',
            'bftv_copyright_request'
        ];
        $tnsSkip      = ['std_user_3rd', 'std_voice_pcklist'];
        $existTables  = [];
        $tablesConfig = [];
        $table        = $dbSys->setText("select id, db_uq, db_name, table_name, create_at, update_at, src_engine, src_pk, src_pk_type, src_size, src_create_sql, is_sync, last_sync_at, sync_sql, sync_type,local_pk_max from dev_db_sync_list where db_uq=:db_uq")->bindArray([':db_uq' => $dbUq])->queryAll();
        foreach ($table as $row)
        {
            $existTables[]                    = $row['table_name'];
            $tablesConfig[$row['table_name']] = $row;
        }
        $tableTns     = $dbSrc->setText("select `TABLE_NAME` as tn,`ENGINE` as 'src_engine', (DATA_LENGTH+INDEX_LENGTH) as src_size from INFORMATION_SCHEMA.tables where table_schema='{$rowSrc['db_name']}' and TABLE_TYPE='BASE TABLE';")->queryAll();
        $table        = $dbLocal->setText("select `TABLE_NAME` as tn from INFORMATION_SCHEMA.tables where table_schema='{$rowSrc['db_name']}' and TABLE_TYPE='BASE TABLE';")->queryAll();
        $existTables2 = [];
        foreach ($table as $row)
            $existTables2[] = $row['tn'];
        foreach ($tableTns as $i => $row)
        {
            $tn = $row['tn'];
            echo "{$i}/{$tn}\n";
            if (in_array($tn, $tnsSkip, true))
                continue;
            $tableConfig = isset($tablesConfig[$tn]) ? $tablesConfig[$tn] : [
                'db_uq'      => $dbUq,
                'db_name'    => $rowLocal['db_name'],
                'table_name' => $tn,
                'src_engine' => $row['src_engine'],
                'src_size'   => $row['src_size'],
            ];

            $srcTableInfo = $dbSrc->setText("show full columns from {$tn};")->queryAll();
            $isPkInt      = false;
            $pk           = '';
            $fields       = [];
            $fields2      = [];
            $fields3      = [];
            foreach ($srcTableInfo as $row2)
            {
                if ($isPkInt === false && $row2['Key'] === 'PRI' && strstr($row2['Type'], 'int('))
                {
                    $pk      = $row2['Field'];
                    $isPkInt = true;
                }
                $fields[]  = "`{$row2['Field']}`";
                $fields2[] = "`{$row2['Field']}`=:{$row2['Field']}_{index}";
                $fields3[] = $row2['Field'];
            }
            $selection   = join(',', $fields);
            $sqlSelctTpl = "select {$selection} from {$tn} where `{$pk}`>{maxVal} order by `{$pk}` asc limit 100 ";
            $sqlInsTpl   = "insert ignore into {$tn} set " . join(',', $fields2);
            $pkType      = '';
            if ($isPkInt === true || strtolower($pk) === 'id')
            {
                $pkType = 'int';
            }
            if (!isset($tableConfig['src_pk_type']) || $tableConfig['src_pk_type'] !== $pkType)
                $tableConfig['src_pk_type'] = $pkType;
            if (!isset($tableConfig['src_pk']) || $tableConfig['src_pk'] !== $pk)
                $tableConfig['src_pk'] = $pk;


            if (in_array($tn, $existTables))
            {
                echo "has created\n";
            }
            else
            {
                echo "not created\n";
                $row2 = $dbSrc->setText("show create table {$tn};")->queryRow();
                if (isset($row2['View']))
                    continue;
                $sql                           = $row2['Create Table'];
                $tableConfig['src_create_sql'] = $sql;
                if (!in_array($tn, $existTables2))
                {
                    $sql = str_replace(['` datetime NOT NULL DEFAULT '], ['`` datetime DEFAULT NULL '], $sql);
                    $dbLocal->setText($sql)->execute();
                }
            }

            $colsTmp = [];
            $bindTmp = [];
            foreach ($tableConfig as $k => $v)
            {
                $colsTmp[]        = "`{$k}`=:{$k}";
                $bindTmp[":{$k}"] = $v;
            }
            $strTmp = join(',', $colsTmp);
            $dbSys->setText("insert ignore into dev_db_sync_list set {$strTmp} on duplicate key update {$strTmp};")->bindArray($bindTmp)->execute();
            if ($tableConfig['src_pk_type'] === '')
            {
                echo date('Y-m-d H:i:s', time()) . "#table index:{$i}/{$tn} not int pk\n";
                continue;
            }

            $limit    = 10000;
            $maxPkVal = $dbLocal->setText("select max(`{$pk}`) from {$tn}")->queryScalar();
            $maxPkVal = intval($maxPkVal);
            $goon     = true;
            $insCount = 0;
            while ($goon)
            {
                $table2 = $dbSrc->setText(str_replace('{maxVal}', $maxPkVal, $sqlSelctTpl))->queryAll();
                if (is_array($table2) && count($table2))
                {
                    $sqls = [];
                    $bind = [];
                    foreach ($table2 as $index => $ele)
                    {
                        $sqls[] = str_replace('{index}', $index, $sqlInsTpl);
                        foreach ($fields3 as $field)
                        {
                            $bind["{$field}_{$index}"] = $ele[$field];
                        }
                        $maxPkVal = $ele[$pk];
                        $insCount++;
                    }
                    $dbLocal->setText(join(';', $sqls))->bindArray($bind)->execute();
                    $sqlCount = count($sqls);
                    if ($limit && $insCount >= $limit)
                    {
                        $goon = false;
                    }
                    if ($sqlCount < 100)
                    {
                        $goon = false;
                    }

                }
                else
                {
                    $goon = false;
                }
                echo date('Y-m-d H:i:s', time()) . "#table index:{$i}/{$tn} --maxPk:{$maxPkVal} insertCount:{$insCount} table limit:{$limit}\n";
            }


            // $dbFuntv->setText($sql)->execute();
            // $this->logStatus($tn, 'coypData', 'ok');


        }
    }

    public function call(){
        $db=Sys::app()->db('funtv_dev');
        for($i=0;$i<100;$i++){
            $sql="call build_table({$i},1);";
           $r= $db->setText("$sql")->execute();
           echo "$sql";
           var_export($r);
           echo "\n";
        }
    }

    public function conf()
    {

        include('/data/code/poseidon_server/phplib/common/dbconf.php');
        $dbconf = array(
            'servers' => array(
                0 => 'mysql:host=10.1.6.1;port=3306',
                1 => 'mysql:host=10.1.6.2;port=3306',
                2 => 'mysql:host=10.1.6.3;port=3306',
                3 => 'mysql:host=10.1.6.4;port=3306',
            ),
            'encode' => 'utf8',
            'db_poseidon_warning' => array(
                'user' => 'poseidon_warning',
                'passwd' => 'pZu854G',
                'dbname' => 'db_poseidon_warning',
            ),
        );

        $confs=array();
        $default_servers=$dbconf['servers'];
        unset($dbconf['servers']);
        unset($dbconf['encode']);
        foreach ($dbconf as $key=>$row){
            \Common_Dbconf::initConf($key);
            $json=json_encode(array(
                'servers'=>isset($row['servers'])?$row['servers']:$default_servers,
                'user'=>$row['user'],
                'passwd'=>$row['passwd'],
                'dbname'=>$row['dbname'],
            ));
            $json2=json_encode(\Common_Dbconf::$dbconf[$key]);
            echo "{$key}\n";
            if($json!==$json2){
                echo "\n-------------{$key}--------------\nthis:\n{$json}\ncommon:\n{$json2}\n";
            }
        }
        //var_dump(\Common_Dbconf::$dbconf);
    }
}