<?php

namespace hammer\db;

//use \cli\CConsole;
//use \models\ConsoleError;

use hammer\sys\Sys;

class MysqlPdo extends \PDO
{
    private $commandText = '';
    /** @var null | MysqlPdoCmd */
    private $cmd      = null;
    private $prefix   = '';
    private $bindData = [];
    private $readOnly = false;
    public  $cts      = '';
    private $cfg      = [];

    /**
     * @param $config
     * @param bool $isAlive
     * @return MysqlPdo/PDO
     */
    public static function configDb($config, $isAlive = false)
    {
        $opt = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            \PDO::ATTR_PERSISTENT         => false
        );
        $opt = array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            \PDO::ATTR_TIMEOUT            => 5,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION
        );
        try
        {
            //var_dump($config['connectionString']);
            $model = new MysqlPdo($config['connectionString'], $config['username'], $config['password'], $opt);
        } catch (\Exception $e)
        {
            Sys::app()->interruption()->setMsg('操作失败' . '数据库链接失败' . $e->getMessage() . $e->getCode())->setDebugData($config)->outError();
        }

        $model->prefix   = isset($config['prefix']) ? trim($config['prefix']) : '';
        $model->readOnly = isset($config['readOnly']) ? $config['readOnly'] : false;
        $model->cts      = date('Y-m-d H:i:s', time());
        $model->cfg      = $config;
        return $model;
    }

    public function __get($attr)
    {
        if (isset($this->$attr))
            return $this->$attr;
        throw new \Exception('could find attr');
    }


    public function setText($commandText)
    {
        return new MysqlPdoCmd($this, $commandText);
    }

    public function getOldPdoCmd()
    {
        return new OldPdoCmd($this);
    }
}



