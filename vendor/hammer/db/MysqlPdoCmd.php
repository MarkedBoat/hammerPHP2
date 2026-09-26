<?php

namespace hammer\db;

//use \cli\CConsole;
//use \models\ConsoleError;

use hammer\sys\Sys;

class MysqlPdoCmd
{
    private $commandText = '';
    private $readOnly    = false;
    public  $cts         = '';
    /** @var MysqlPdo */
    public $db       = null;
    public $bindData = [];
    /** @var \PDOStatement */
    public $cmd = null;

    public function __construct($db, $commandText)
    {
        $this->db = $db;
        $this->setText($commandText);
        return $this;
    }

    public function setText($commandText)
    {
        if ($this->readOnly && (strstr($commandText, 'insert ') || strstr($commandText, 'update ')))
            Sys::app()->interruption()->setMsg('操作失败')->setCode('mysql_error_readonly')->outError();
        try
        {
            $this->commandText = $this->db->prefix ? preg_replace('/{(.*?)}/', $this->db->prefix . '_$1', $commandText) : $commandText;
            $this->cmd         = $this->db->prepare($this->commandText);
        } catch (\PDOException $e)
        {
            Sys::app()->interruption()->setMsg('操作失败')->setCode('mysql_error_exec_error')->setDebugMsg($e->getMessage())->setDebugData([
                $this->commandText,
                $this->bindData
            ])->outError();

        }
        return $this;
    }

    /**
     * @return MysqlPdo/PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    public function getText()
    {
        return $this->commandText;
    }

    public function bind($bindKey, $param)
    {
        $this->bindData[$bindKey] = $param;
        $this->cmd->bindValue($bindKey, $param);
        return $this;
    }

    public function bindArray($array)
    {
        try
        {
            $this->bindData = [];
            foreach ($array as $bindKey => $bindValue)
            {
                $this->bindData[$bindKey] = $bindValue;
                $this->cmd->bindValue($bindKey, $bindValue);
            }
        } catch (\PDOException $e)
        {
            Sys::app()->interruption()->setMsg('操作失败')->setCode('mysql_error_exec_error')->setDebugMsg($e->getMessage())->setDebugData([
                $this->commandText,
                $this->bindData
            ])->outError();
        }

        return $this;
    }

    public function getDebugInfo()
    {
        preg_match_all('/:\w+/', $this->commandText, $ar);
        $bindKeys = array_keys($this->bindData);
        return [
            'sql'         => $this->commandText,
            'bind'        => $this->bindData,
            'replqce_sql' => str_replace(array_keys($this->bindData), array_map(function ($v) { return "'{$v}'"; }, array_values($this->bindData)), $this->commandText),
            $this->getDb(),
            ['bindMore' => array_diff($bindKeys, $ar[0]), 'sqlMore' => array_diff($ar[0], $bindKeys)]
        ];
    }

    private function __execute()
    {
        try
        {
            $this->cmd->execute();
            return $this->cmd->rowCount();
        } catch (\PDOException $e)
        {

            Sys::app()->getLogger()->log('sql_error',[$e->getMessage(), $this->getDebugInfo()]);
            Sys::app()->interruption()->setMsg('操作失败' . $e->getMessage())->setCode('mysql_error_exec_error')->outError();
        }
    }

    public function execute()
    {
        return $this->__execute();
    }

    public function queryAll()
    {
        $this->__execute();
        return $this->cmd->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function queryRow()
    {
        $this->__execute();
        if (!$this->cmd->rowCount())
            return false;
        $result = array();
        try
        {
            while ($row = $this->cmd->fetch())
            {
                $result = $row;
                break;
            }
        } catch (\PDOException $e)
        {
            Sys::app()->interruption()->setMsg('读取失败')->setCode('mysql_error_exec_error')->setDebugMsg($e->getMessage())->setDebugData([
                $this->commandText,
                $this->bindData
            ])->outError();
        }

        return $result;
    }

    public function queryScalar()
    {
        $this->__execute();
        if (!$this->cmd->rowCount())
            return false;
        $this->cmd->setFetchMode(\PDO::FETCH_BOTH);
        $result = array();
        while ($row = $this->cmd->fetch())
        {
            $result = $row;
            break;
        }
        return $result[0];
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

}

?>