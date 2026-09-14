<?php


    namespace cli\dev\statis;

    use hammer\cli\CmdBase;
    use hammer\sys\Sys;

    ini_set('memory_limit', '2048M');


    class CmdTmp extends CmdBase {

        public static function getClassName() {
            return __CLASS__;
        }


        public function init() {
            parent::init();
        }

        /**
         * 导出连过网的
         */
        public function import() {
            $db       = Sys::app()->db('dev0');
            $csv      = '/data/upload/log.csv';
            $f        = fopen($csv, 'r');
            $fileLine = 0;
            $rows     = [];
            while (!feof($f)) {
                $fileLine++;
                $str        = trim(fgets($f));
                $uuidsVip[] = $str;
                echo $fileLine . '#' . date('H:i:s', time()) . '#' . $str . "\n";
                $ar = explode(',', $str);
                if (count($ar) === 4) {
                    $rows[] = $ar;
                    if (count($rows) === 1000) {
                        $this->insertDb($db, $rows);
                        $rows = [];
                    }
                }
            }
            if (count($rows)) {
                $this->insertDb($db, $rows);
                $rows = [];
            }
            fclose($f);
        }

        public function insertDb($db, $rows) {
            $sqls = [];
            $bind = [];
            foreach ($rows as $i => $row) {
                $sqls[]          = "insert ignore into tmp set redis='{$row[1]}',db_index={$row[2]},k=:k_{$i}";
                $bind[":k_{$i}"] = $row[3];
            }
            $db->setText(join(';', $sqls))->bindArray($bind)->execute();

        }

        public function mysql() {
            $db    = Sys::app()->db('dev0');
            $sql   = 'insert ignore into tmp_mysql_config set db_host=:host,db_name=:name,db_un=:un,db_psw=:psw,file_name=:file_name,file_line=:file_line,file_project=:file_project,file_env=:file_env,str=:str on duplicate  key update db_host=:host';
            $array = explode("\n", file_get_contents('/data/upload/mysql.log'));
            $p     = '/host\=(.*)?\;/i';
            $p     = '/host=(.*?);/i';
            foreach ($array as $i => $str) {
                $str = trim($str);
                if (empty($str))
                    continue;
                echo "{$i}:{$str}\n";

                $bind = [
                    ':host'         => '',
                    ':name'         => '',
                    ':un'           => '',
                    ':psw'          => '',
                    ':file_name'    => '',
                    ':file_line'    => '',
                    ':file_project' => '',
                    ':file_env'     => '',
                    ':str'          => $str,
                ];
                preg_match_all('/host=(.*?);/i', $str, $ar);
                if (isset($ar[1][0])) {
                    $bind[':host'] = trim(trim($ar[1][0], "'"), '.');
                } else {
                    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
                    continue;
                }
                preg_match_all('/dbname=(.*?)(;|\')/i', $str, $ar);
                if (isset($ar[1][0])) {
                    $bind[':name'] = trim(trim($ar[1][0], "'"), '.');
                } else {
                    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!";
                    continue;
                }

                preg_match_all('/username[\'|\"]?s?=>\s?[\'|\"](.*?)[\'|\"|;]/i', $str, $ar);
                if (isset($ar[1][0])) {
                    $bind[':un'] = trim(trim($ar[1][0], "'"), '.');
                }
                preg_match_all('/password[\'|\"]?s?=>\s?[\'|\"](.*?)[\'|\"|;]/i', $str, $ar);
                if (isset($ar[1][0])) {
                    $bind[':psw'] = trim(trim($ar[1][0], "'"), '.');
                }

                $ar2 = explode(':', $str);

                $bind[':file_name']    = $ar2[0];
                $bind[':file_line']    = $ar2[1];
                $ar3                   = explode('/', $ar2[0]);
                $bind[':file_project'] = $ar3[3];
                $bind[':file_env']     = str_replace('.php', '', array_pop($ar3));
                echo "\n" . json_encode($bind, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $db->setText($sql)->bindArray($bind)->execute();
                echo "\n------------------------------------------------------------------\n";
            }

        }


    }