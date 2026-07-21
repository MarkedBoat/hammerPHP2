<?php

    namespace modules\sl\v1\api;

    use hammer\web\ActionBase;
    use hammer\sys\Sys;


    class ActionTest extends ActionBase {
        public static function getClassName() {
            return __CLASS__;
        }

        public function __construct($param = []) {
            parent::init($param);
        }

        public function run() {


            $table = $this->inputBox->getNotEmptyString('table');
            // $table = 'sl_client';
            $sql = "show full columns from {$table};";
            $config=Sys::app()->getConfig();
          //  var_dump($config['db']['db_fcg_repository']);
          //  var_dump($config['db']['T_db_fcg_repository']);
            $table = Sys::app()->db('db_fcg_repository')->setText($sql)->queryAll();

            echo '<pre>';
            $attrs = [];
            foreach ($table as $row) {
                $type = strstr($row['Type'], 'int') ? 'int' : 'string';
                echo "* @property {$type} {$row['Field']} {$row['Comment']}\n";
                $attrs[] = "'{$row['Field']}'";
            }
            echo "\n";
            $str  = join(',', $attrs);
            $str2 = str_replace("'", '`', $str);
            echo " const fields = '{$str2}';\n";

            echo "\n\n\n";
            foreach ($table as $row) {
                echo " public \${$row['Field']}; //{$row['Comment']}\n";
            }
            echo " protected \$allAttrKeys = [{$str}];\n";

            die('</pre>');
        }

    }


