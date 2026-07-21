<?php

namespace modules\dev\v1\api\mysql;

use hammer\web\ActionBase;
use hammer\sys\Sys;


class ActionDaoOut extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {

        ini_set('display_errors', 1);
        error_reporting(11);

        Sys::app()->setDebug(true);

        $tablename = $this->inputBox->getNotEmptyString('table');
        $classname = join('', array_map(function ($item) { return ucfirst($item); }, explode('_', $tablename)));
        $dbkey     = $this->inputBox->getNotEmptyString('db');
        // $table = 'sl_client';
        $sql   = "show full columns from {$tablename};";
        $table = Sys::app()->db($dbkey)->setText($sql)->queryAll();

        echo '<pre>';
        $str  = htmlspecialchars('<?php');
        $date = date('Y/m/d H:i:s');
        echo $str;
        echo "
        
        namespace modules;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @date {$date}
 * @author ahyjl@126.com
 * @example
 * @link
 * @desc
 * Class {$classname}\n";

        $attrs = [];
        foreach ($table as $row)
        {
            $type = strstr($row['Type'], 'int') ? 'int' : 'string';
            $dbType=$row['Type'];
            echo "* @property {$type} {$row['Field']} {$row['Comment']} ({$dbType})\n";
            $attrs[] = "'{$row['Field']}'";
        }
        echo "*/\n";

        echo "CLass {$classname} extends DbModel{\n";
        $str = join(',', $attrs);

        foreach ($table as $row)
        {
         //   echo "      public \${$row['Field']}; //{$row['Comment']}\n";
        }
        echo "      protected \$allAttrKeys = [{$str}];\n";

        $str2 = str_replace("'", '`', $str);
        echo "      const fields = '{$str2}';\n\n";

        echo "\n\n\n";

        echo "

      
    const tableName = '{$tablename}';

    public function getTableName(): string
    {
        return self::tableName;
    }

    /**
     * @throws \Exception
     */
    public function getConnection(): \hammer\db\MysqlPdo
    {
        return Sys::app()->db('{$dbkey}');
    }
            public function getOpenInfo()
        {
            return [
          
    ";

        foreach ($table as $row)
        {
            echo "        '{$row['Field']}'=>\$this->{$row['Field']},\n";
        }

        echo '  
                   ];
        }
';

        die('}</pre>');

    }

}