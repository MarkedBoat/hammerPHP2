<?php

namespace hammer\db;


use hammer\sys\Sys;


/**
 * @date   2025/4/9 14:26
 * @author user0558@qq.com (automatic generation,maybe not creater)
 * @example
 * @link
 * @desc
 * Class DbData
 * @property $PDO                    PDO
 * @property $lastSql                string
 * @property $rowCount               int
 * @property $errMessage             string
 * @property $printer                Tool_Printer
 * @property $auto_connect_db        bool
 * @property $has_connected_db       bool
 * @property $exec_opts              array
 * @property $exec_opts_default      array
 * @property $table                  string
 * @property $pk                     string
 * @property $confName               string
 * @property $has_checked_tablenames array
 * @property $not_existed_tablenames array
 * @property $instance_pool          array
 * @property $suffixDateFormat       string|false  false|ym/ymd/Y_m/Y_m_d 代表后缀格式
 * @property $timeSizeField          string   时间粒度字段 会寻找 ymd/ym/y/year 关键字，自行判断时间粒度,主要给分表的,以后也遵循这个标准
 */
class OldPdoCmd
{
    private $commandText = '';
    private $readOnly    = false;
    public  $cts         = '';
    /** @var MysqlPdo */
    public $db       = null;
    public $bindData = [];
    /** @var \PDOStatement */
    public $cmd = null;


    private $lastSql;
    private $rowCount;
    private $errMessage;


    public function __construct($db)
    {
        $this->db = $db;
        return $this;
    }
    /**
     * @throws Exception
     */
    public function errorMsg($msg, $data = [])
    {
        throw new \Exception($msg);
    }
    public function setSql($commandText)
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

    public function getSql()
    {
        return $this->commandText;
    }


    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }


    public function exeucte($sql, $param = array())
    {

        $this->setSql($sql);

        if (true)
        {
            //   var_dump([$sql,$param]);
            $sql2 = strtolower($sql);
            if ((strstr($sql2, 'insert') || strstr($sql2, 'update')))
            {
                $ar = explode('?', $sql2);
                foreach ($param as $i => $v)
                {
                    $ar[$i] .= (is_numeric($v) ? $v : "'{$v}'");
                }
                //   echo "\n error_sql {$list_sql_replace}\n";
            }


        }

        if (Sys::app()->isDebug())
        {
            $ar = explode('?', $sql);
            foreach ($param as $i => $v)
            {
                $ar[$i] .= (is_numeric($v) ? $v : "'{$v}'");
                if (is_array($v))
                {
                    var_dump($param, $sql);
                    var_dump($v);
                    die;
                }
            }
            //   echo "\n error_sql {$list_sql_replace}\n";
            Sys::app()->logData([$sql, $param, join('', $ar)], 'sql_execute', true, false);
        }
        try
        {
            $pre = $this->db->prepare($sql);

            $pre->execute($param);
            $this->lastSql = $pre->queryString;
            return $pre;
        } catch (Exception $e)
        {
            $ar = explode('?', $sql);
            foreach ($param as $i => $v)
            {
                $v      = is_null($v) ? 'NULL' : (is_numeric($v) ? $v : "'{$v}'");
                $ar[$i] .= $v;
            }
            $list_sql_replace = join('', $ar);

            if (Sys::app()->workMode === 'cli')
            {
                echo "\n\n{$list_sql_replace}\n{$sql}\n";
                var_dump($param);
                echo "\n{$e->getMessage()}\n";
                echo $e->getTraceAsString();
                echo "\n\n\n";
            }
            throw $e;
        }


    }

    public function queryScalar($sql, $param = array(), $col = 0)
    {
        return $this->exeucte($sql, $param)->fetchColumn($col);
    }

    public function queryKV($sql, $param = array())
    {
        return $this->exeucte($sql, $param)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }


    /**
     * @param Tool_Printer $printer
     *
     * @return static
     */
    public function setPrinter(Tool_Printer $printer): DbData
    {
        $this->printer = $printer;
        return $this;
    }

    /**
     * @return false|PDOStatement
     * @throws Exception
     */
    public function execute($sql, $param = array())
    {
        if (($this->execSetting['tmpDebug'] ?? false))
        {
            gf_shell_echo($sql);
        }
        if (true && Sys::app()->workMode === 'cli')
        {
            $sql2 = strtolower($sql);
            if ((strstr($sql2, 'insert') || strstr($sql2, 'update')) && strstr($sql2, 'log_ymd_user__item_2025_06') && (strstr($sql2, '20250613')))
            {
                $ar = explode('?', $sql2);
                foreach ($param as $i => $v)
                {
                    $ar[$i] .= (is_numeric($v) ? $v : "'{$v}'");
                }
                //   echo "\n error_sql {$list_sql_replace}\n";

                Sys::app()->setFileLog(false)->log('sql_execute:' . json_encode([$sql2, $param, join('', $ar), debug_backtrace()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }


        }

        //todo 在这替换 biz_log. 等
        // TODO auto reconnect
        if ($this->exec_opts['well_msg'] ?? false)
        {
            $this->exec_opts['well_msg'] = false;

        }
        if (Sys::app()->isDebug())
        {
            $ar = explode('?', $sql);
            foreach ($param as $i => $v)
            {
                $ar[$i] .= (is_numeric($v) ? $v : "'{$v}'");
                if (is_array($v))
                {
                    var_dump($param, $sql);
                    var_dump($v);
                    die;
                }
            }
            //   echo "\n error_sql {$list_sql_replace}\n";
            Sys::app()->setFileLog(false)->logData([$sql, $param, join('', $ar)], 'sql_execute', true, false);
        }

        try
        {
            $pre = $this->db->prepare($sql);
            $pre->execute($param);
            $this->lastSql = $pre->queryString;
        } catch (Exception $e)
        {
            $ar = explode('?', $sql);
            foreach ($param as $i => $v)
            {
                $v      = is_null($v) ? 'NULL' : (is_numeric($v) ? $v : "'{$v}'");
                $ar[$i] .= $v;
            }
            $list_sql_replace = join('', $ar);

            if (Sys::app()->debugAllowInterrupt)
            {
                echo "\n error_sql {$list_sql_replace}\n";
            }


            if (Sys::app()->workMode === 'cli')
            {
                echo "\n\n{$list_sql_replace}\n{$sql}\n";
                var_dump($param);
                echo "\n{$e->getMessage()}\n";
                echo $e->getTraceAsString();
                echo "\n\n\n";
            }
            throw $e;
        }


        return $pre;
    }


    /**
     * 开启事务
     *
     * @return bool
     */
    public function begin(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * 事务提交
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }

    public function getColumn($sql, $param = array(), $col = 0)
    {
        return $this->execute($sql, $param)->fetchColumn($col);
    }

    public function getKeyValue($sql, $param = array())
    {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function getCount($sql, $param = array())
    {
        return $this->execute($sql, $param)->rowCount();
    }

    public function getAll($sql, $param = array())
    {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function queryRows($sql, $param = array())
    {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getArrColumn($sql, $param = array(), $col = 0)
    {
        return $this->execute($sql, $param)->fetchAll(\PDO::FETCH_COLUMN, $col);
    }

    /**
     * @throws Exception
     */
    public function queryRow($sql, $param = array())
    {
        return $this->execute($sql, $param)->fetch(\PDO::FETCH_ASSOC);
    }


    public function getLastSQL()
    {
        return $this->lastSql;
    }

    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function getError()
    {
        return $this->errMessage;
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function get($id, $field = '*')
    {
        return $this->getRow("SELECT {$field} FROM {$this->table} WHERE {$this->pk}=?", array($id));
    }


    /**
     * @param string $col
     * @param string $table
     * 查询表的字段
     *
     * @return mixed
     */
    public function getColInfo($col, $table = '')
    {
        if (empty($table))
        {
            $table = $this->table;
        }
        return $this->getRow("SHOW COLUMNS FROM {$table} WHERE FIELD LIKE ?", array($col));
    }

    /**
     * @param string $col
     * @param string $table
     * 快速获取枚举类型列表
     *
     * @return array
     */
    public function getColEnum($col, $table = '')
    {
        $col_info = $this->getColInfo($col, $table);
        $enum     = explode(',', preg_replace('/^enum\((.*)\)$/i', '$1', $col_info['Type']));
        return array_map(function ($v) { return trim($v, '\''); }, $enum);
    }


    /** @deprecated DbData中废弃,只是为了兼容 */
    public function add($data)
    {
        return $this->insert($this->table, $data);
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function edit($id, $data)
    {
        return $this->mod($id, $data);
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function mod($id, $data)
    {
        return $this->update($this->table, $data, array($this->pk => $id));
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function set($ids, $field, $value)
    {
        return $this->update($this->table, array($field => $value), array($this->pk => $ids));
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function del($ids)
    {
        return $this->delete($this->table, array($this->pk => $ids));
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function upsert($keys, $data, $insert_only_data = [])
    {
        $sql = 'SELECT ' . $this->pk . ' FROM ' . $this->table . ' WHERE ' . $this->makeWhereSQL($keys, 'AND', $params);
        $id  = $this->getColumn($sql, $params);
        if ($id > 0)
        {
            return $this->update($this->table, $data, [$this->pk => $id]);
        }
        else
        {
            return $this->add(array_merge($keys, $data, $insert_only_data));
        }
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function setDec($where, $field, $num = 1)
    {
        $num    = intval($num);
        $params = [];
        $sql    = 'UPDATE ' . $this->table . ' SET ' . $field . '=' . $field . '-' . $num . ' WHERE ' . $this->makeWhereSQL($where, 'AND', $params);
        return $this->execute($sql, $params)->rowCount();
    }

    /** @deprecated DbData中废弃,只是为了兼容 */
    public function setInc($where, $field, $num = 1)
    {
        $num    = intval($num);
        $params = [];
        $sql    = 'UPDATE ' . $this->table . ' SET ' . $field . '=' . $field . '+' . $num . ' WHERE ' . $this->makeWhereSQL($where, 'AND', $params);
        return $this->execute($sql, $params)->rowCount();
    }


    /**
     * @param string $tableName
     * @param array  $whereAttributes [ 'id'=>[ $in=>[1,2,3] ]  ]    [ 'id'=>[1,2,3] ,'id2'=>["$gte"=>1]  ]
     * @param array  $selectFields    []  [*] [a,b,c]
     * @param array  $sortInfo        ['id'=>'desc']
     * @param array  $pageInfo        ['page_index' => 1, 'page_size' => $limit]
     *
     * @return array|void
     */
    public function makeSqlAndBind(string $tableName, array $whereAttributes, array $selectFields, array $sortInfo, array $pageInfo)
    {
        $sym_map  = [
            '$gt'  => '>',
            '$gte' => '>=',
            '$lt'  => '<',
            '$lte' => '<=',
            '$eq'  => '=',
        ];
        $sort_map = [
            -1 => 'desc',
            1  => 'asc',
        ];
        $cs       = [];
        $binds    = [];
        foreach ($whereAttributes as $field => $val)
        {
            if (is_numeric($field) && is_string($val))
            {
                $cs[] = $val;
            }
            else if (is_array($val))
            {
                if (isset($val[0]))
                {
                    $cs2 = [];
                    foreach ($val as $v)
                    {
                        $binds[] = $v;
                        $cs2[]   = '?';
                    }
                    $cs[] = " `{$field}` in (" . join(',', $cs2) . ")";
                }
                else
                {
                    foreach ($val as $mongo_type => $v2)
                    {
                        if ($mongo_type === '$in')
                        {
                            $cs2 = [];
                            foreach ($val as $v3)
                            {
                                $binds[] = $v3;
                                $cs2[]   = '?';
                            }
                            $cs[] = " `{$field}` in (" . join(',', $cs2) . ")";
                        }
                        else if ($mongo_type === '$regex')
                        {
                            $cs[]    = "`{$field}` like ?";
                            $binds[] = "%{$v2}%";
                        }
                        else
                        {
                            if (isset($sym_map[$mongo_type]))
                            {
                                $cs[]    = "`{$field}`{$sym_map[$mongo_type]}?";
                                $binds[] = $v2;
                            }
                            else
                            {
                                die("这是什么玩意? mongo_type:{$mongo_type}");
                            }
                        }
                    }
                }
            }
            else
            {
                $cs[]    = "`{$field}`=?";
                $binds[] = $val;
            }
        }

        $sort_strs = [];
        foreach ($sortInfo as $filed => $sort_type)
        {
            $filed2 = (strstr($filed, '.') || strstr($filed, '(')) ? $filed : "`{$filed}`";
            if (in_array($sort_type, ['asc', 'desc']))
            {
                $sort_strs[] = "{$filed2} {$sort_type}";
            }
            else if (isset($sort_map[$sort_type]))
            {
                $sort_strs[] = "{$filed2} {$sort_map[$sort_type]}";
            }
            else
            {
                die("这是什么玩意? sort_type:{$sort_type}");
            }
        }
        $limit_strs = [];
        if (isset($pageInfo['page_size']))
        {
            $page_size = intval($pageInfo['page_size']);
            if ($page_size > 0)
            {
                if (isset($pageInfo['page_index']))
                {
                    $page_index   = intval($pageInfo['page_index']);
                    $limit_strs[] = (($page_index > 0 ? $page_index : 1) - 1) * $page_size;
                }
                else
                {
                    $limit_strs[] = 0;
                }
                $limit_strs[] = $page_size;
            }
        }

        $filed_str = count($selectFields) === 0 ? '*' : join(',', array_map(function ($f) { return "`{$f}`"; }, $selectFields));
        $where_str = count($cs) === 0 ? '' : (' where ' . join(' and ', $cs));
        $sort_str  = count($sort_strs) === 0 ? '' : (' order by ' . join(',', $sort_strs));
        $limit_str = count($limit_strs) === 0 ? '' : (' limit ' . join(',', $limit_strs));
        return [

            "select {$filed_str} from {$tableName}  {$where_str} {$sort_str}  {$limit_str}",
            $binds,
            "select count(*) as tmp_cnt from {$tableName}  {$where_str} ",


        ];
    }

    public function queryAllByAttrs($attrs, $cols, $sort = [], $page = [0, 20], $groupBy = [], $having = [])
    {
        list($query_sql, $binds, $count_sql) = $this->makeSqlAndBind($this->table, $attrs, $cols, $sort, $page, $groupBy, $having);
        Sys::app()->logData($query_sql, 'queryAllByAttrs query_sql', false, false);
        return $this->getAll($query_sql, $binds);
    }

    /**
     * @param $columns        array
     * @param $update_columns array 冲突时要修改的数据,不填写就是  try insert ，填了就是 upsert
     *
     * @return int|false
     * @deprecated DbData中废弃,只是为了兼容
     */
    public function tryInsertOrUpsert(array $columns, array $update_columns = [], $returnType = 'rowCount')
    {
        $insert_sets = [];
        $update_sets = [];
        $vals        = [];
        foreach ($columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $insert_sets[] = "{$v}";
            }
            else
            {
                $insert_sets[] = "`{$k}`=?";
                $vals[]        = $v;
            }

        }
        foreach ($update_columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $update_sets[] = "{$v}";
            }
            else
            {
                $update_sets[] = "`{$k}`=?";
                $vals[]        = $v;
            }

        }

        $insert_set_strs = join(',', $insert_sets);
        if (count($update_sets) === 0)
        {
            $sql = "insert ignore into {$this->table} set {$insert_set_strs} ";

        }
        else
        {
            $update_set_strs = join(',', $update_sets);
            $sql             = "insert into {$this->table} set {$insert_set_strs} on duplicate key update {$update_set_strs}";
        }
        $count = $this->execute($sql, $vals)->rowCount();

        if ($returnType === 'rowCount')
        {
            return $count;
        }
        else
        {
            return $count > 0 ? $this->getLastInsertId() : false;
        }
        // gf_shell_echo(var_export([$sql,$insert_vals,$res],true));


    }

    protected function info2JsonObjectStr($info, $isDeep0 = true)
    {
        if (is_array($info))
        {
            $ar = [];
            foreach ($info as $k => $v)
            {
                $v2 = '';
                if (is_array($v))
                {
                    $v2 = $this->info2JsonObjectStr($v, false);
                }
                else
                {
                    $v2 = is_int($v) ? $v : "'{$v}'";
                }
                if ($isDeep0)
                {
                    $ar[] = "'$.{$k}',{$v2}";
                }
                else
                {
                    $ar[] = "'{$k}',{$v2}";
                }
            }
            $tmp_str = join(',', $ar);
            if ($isDeep0)
            {
                return $tmp_str;
            }
            else
            {
                return "json_object($tmp_str)";
            }

        }
        else
        {
            throw new SysException('neither array nor object: ' . json_encode($info));
        }


    }


    /**
     * @param      $table_name
     * @param bool $force
     *
     * @return bool
     */
    public function isExistTablename($table_name, bool $force = false): bool
    {
        if ($force)
        {
            $this->clearNotExistedTableRecord();
        }
        else
        {
            if (in_array($table_name, $this->not_existed_tablenames))
            {
                return false;
            }
        }

        if (in_array($table_name, $this->has_checked_tablenames, true))
        {
            return true;
        }
        else
        {
            $tn = str_replace('_', '\_', $table_name);
            if (!$this->getRow("SHOW TABLES LIKE '{$tn}'"))
            {
                $this->not_existed_tablenames[] = $table_name;
                return false;
            }
            else
            {
                $this->has_checked_tablenames[] = $table_name;
                return true;

            }
        }
    }

    /**
     * 清理 不存在表的检测结果
     *
     * @return static
     */
    public function clearNotExistedTableRecord()
    {
        $this->not_existed_tablenames = [];
        return $this;
    }


    /**
     * 尝试添加记录到数据库表中，如果记录已存在，则更新相应字段的值。
     *
     * 本函数用于处理数据的插入或更新操作。当尝试插入一条记录时，如果该记录的唯一键已存在，
     * 则会更新该记录的值。这通常用于处理数据的增量更新，确保数据的最新性。
     *
     * @param $upInsertSql
     * @param $binds
     *
     * @return int|true|false 如果插入或更新操作成功 ,int:代表插入成功的id,false:代表插入失败,true:代表更新成功
     */
    public function upInsertRecord($upInsertSql, $binds)
    {

        // 执行插入操作，并在唯一键冲突时更新值。返回影响的行数。
        $rows_cnt = $this->execute($upInsertSql, $binds)->rowCount();

        // 如果影响的行数大于0，说明有行被插入或更新，返回新插入记录的ID。
        if ($rows_cnt > 0)
        {
            $last_id = intval($this->getLastInsertId());
            if ($last_id)
            {
                return $last_id;
            }
            else
            {
                return true;
            }
        }
        // 如果影响的行数为0，说明没有执行任何插入或更新操作，返回false表示操作失败。
        else
        {
            return false;
        }
    }


    /**
     * 尝试添加记录到数据库表中，如果记录已存在，则返回 false
     *
     * 本函数用于处理数据的插入
     *
     * @param $try_insert_sql
     * @param $binds
     *
     * @return int|false 如果插入或更新操作成功，则返回新插入记录的ID；如果操作失败，则返回false。
     */
    public function tryAddRecord($try_insert_sql, $binds)
    {
        // 执行插入操作，并在唯一键冲突时更新值。返回影响的行数。
        $this->begin();
        try
        {
            $rows_cnt = $this->execute($try_insert_sql, $binds)->rowCount();
            if ($rows_cnt > 0)
            {
                $id = $this->getLastInsertId();
                $this->commit();
                return intval($id);
            }
            $this->rollBack();

        } catch (Exception $e)
        {
            //var_dump($e->getMessage());
            $this->rollBack();
            return false;
        }
    }


    /**
     * 尝试安全插入数据并返回插入ID,不抛异常     *
     *
     * @param string $try_insert_sql 要尝试执行的插入SQL语句,不要带ignore 和 on duplicate key update 不然跳行
     * @param array  $binds          SQL语句中需要绑定的参数。
     *
     * @return int|false  int:成功插入的id，false:插入失败,存在或者字段不对。
     */
    public function onlyTrySafeInsert(string $try_insert_sql, array $binds, $return_row_id = true)
    {
        try
        {
            // 执行插入SQL语句并获取受影响的行数
            $rows_cnt = $this->execute($try_insert_sql, $binds)->rowCount();
            // 如果有行受到影响，则尝试获取新插入行的ID
            if ($rows_cnt > 0)
            {
                return $return_row_id ? intval($this->getLastInsertId()) : true;
            }
            // 如果没有行受到影响，则不返回ID
        } catch (Exception $e)
        {
            //var_dump($e->getMessage());
            // 如果执行过程中发生异常，则返回false
            return false;
        }
    }

    /**
     *
     * 1.先检查是否存在，1.1如果存在走修改  1.2 不存->插入->[成功返回 ,失败则尝试修改 ]
     * <br> 主要返回类型不如  checkUpsert
     *
     * @param array  $where_columns  这其实是  组合唯一索引，如果不存在也是要被插入的
     * @param array  $update_columns 如果存在，就只修改这些值
     * @param string $returnType     rows_cnt:rows_cnt,   pk:row.pk  all:[rows_cnt,pk]
     * @param bool   $autoUpdate     !!!!!自动更新，   默认为true 自动更新，   false:不更新
     *
     * @return false|int|array|string
     * @throws Exception
     */
    public function checkExistAndUpsert(array $where_columns, array $update_columns = [], string $returnType = 'cnt', bool $autoUpdate = true)
    {
        $insert_sets = [];
        $insert_vals = [];

        $where_strs  = [];
        $update_sets = [];
        $update_vals = [];
        $where_vals  = [];

        foreach ($update_columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $update_sets[] = "{$v}";
            }
            else
            {
                $update_sets[] = "`{$k}`=?";
                $update_vals[] = $v;
            }
        }


        foreach ($where_columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $insert_sets[] = "{$v}";
                $where_strs[]  = "{$v}";
            }
            else
            {
                $insert_sets[] = "`{$k}`=?";
                $insert_vals[] = $v;

                $where_strs[]  = "`{$k}`=?";
                $update_vals[] = $v;
                $where_vals[]  = $v;
            }
        }
        $where_str = join(' and ', $where_strs);
        //var_dump("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
        $exist_pk = $this->getColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
        if (empty($exist_pk))
        {
            foreach ($update_columns as $k => $v)
            {
                if (is_numeric($k))
                {
                    $insert_sets[] = "{$v}";
                }
                else
                {
                    $insert_sets[] = "`{$k}`=?";
                    $insert_vals[] = $v;
                }
            }

            $insert_set_strs = join(',', $insert_sets);

            $insert_sql = "insert into {$this->table} set {$insert_set_strs} ";

            try
            {
                $count = $this->wellMsgExecute($insert_sql, $insert_vals)->rowCount();

                if ($returnType === 'rows_cnt')
                {
                    return $count;
                }
                else
                {
                    $last_id = $count > 0 ? $this->getLastInsertId() : false;
                    return $returnType === 'pk' ? $last_id : [$count, $last_id];
                }
            } catch (Exception $e)
            {
                if ($autoUpdate === false)
                {
                    $exist_pk = $this->getColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
                    return $returnType === 'rows_cnt' ? false : ($returnType === 'pk' ? $exist_pk : [false, $exist_pk]);
                }

                $update_set_strs = join(',', $update_sets);
                $update_sql      = "update {$this->table} set {$update_set_strs} where {$where_str}";
                $count           = $this->wellMsgExecute($update_sql, $update_vals)->rowCount();
                if ($returnType === 'rows_cnt')
                {
                    return $count;
                }
                else if ($returnType === 'pk')
                {
                    return $this->getColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
                }
                else
                {
                    return [$count, $this->getColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals)];
                }
            }

        }
        else
        {
            if ($autoUpdate === false)
            {
                return $returnType === 'rows_cnt' ? false : ($returnType === 'pk' ? $exist_pk : [false, $exist_pk]);
            }
            $update_set_strs = join(',', $update_sets);
            $update_sql      = "update {$this->table} set {$update_set_strs} where {$where_str}";
            $count           = $this->wellMsgExecute($update_sql, $update_vals)->rowCount();
            if ($returnType === 'rows_cnt')
            {
                return $count;
            }
            else if ($returnType === 'pk')
            {
                return $exist_pk;
            }
            else
            {
                return [$count, $exist_pk];
            }
        }


        // gf_shell_echo(var_export([$sql,$insert_vals,$res],true));


    }

    /**
     * @throws Exception
     */
    public function getWellMsgColumn($sql, $param = array(), $col = 0)
    {
        return $this->wellMsgExecute($sql, $param)->fetchColumn($col);
    }

    /**
     * 1.先检查是否存在，1.1如果存在走修改  1.2 不存->插入->[成功返回 ,失败则尝试修改 ]
     * <br> 比 checkExistAndUpsert 的返回类型做了优化
     *
     * @param array $unique_or_where_columns 组合唯一索引，用来查询是否存在，不存在时，也作为插入值
     * @param array $update_columns          如果存在，就只修改这些值，不存在也作为插入值.
     *                                       <br> 只填写 update_columns  而不填写 unique_or_where_columns  那就是只管插入
     * @param bool  $update_exist            !!!!!自动更新，   默认为true 自动更新，   false:不更新
     * @param array $insert_data             在检查不存在时 作为插入值.
     *
     * @return DbDataExecRes
     * @throws Exception
     */
    public function checkUpsert(array $unique_or_where_columns, array $update_columns = [], bool $update_exist = true, array $insert_data = [])
    {
        $res_m = new DbDataExecRes();

        $insert_sets = [];
        $insert_vals = [];

        $where_strs  = [];
        $update_sets = [];
        $update_vals = [];
        $where_vals  = [];

        foreach ($update_columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $update_sets[] = "{$v}";
            }
            else
            {
                $update_sets[] = "`{$k}`=?";
                $update_vals[] = $v;
            }
        }


        foreach ($unique_or_where_columns as $k => $v)
        {
            if (is_numeric($k))
            {
                $insert_sets[] = "{$v}";
                $where_strs[]  = "{$v}";
            }
            else
            {
                $insert_sets[] = "`{$k}`=?";
                $insert_vals[] = $v;

                $where_strs[]  = "`{$k}`=?";
                $update_vals[] = $v;
                $where_vals[]  = $v;
            }
        }
        $is_need_check = false;
        $where_str     = '';
        $exist_pk      = false;
        if (count($unique_or_where_columns) > 0)
        {
            $res_m->isTryChcked = true;
            $is_need_check      = true;
            $where_str          = join(' and ', $where_strs);
            //var_dump("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
            $exist_pk = $this->getWellMsgColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
            if (!empty($exist_pk))
            {
                $res_m->isExist = true;
                $res_m->pkVal   = $exist_pk;
            }
            $res_m->resultPath[] = 'has_where,try_check';
        }
        else
        {
            $res_m->resultPath[] = 'no_where,no_check';
        }

        if (empty($exist_pk))
        {
            $res_m->resultPath[] = 'try_insert';
            foreach ($update_columns as $k => $v)
            {
                if (is_numeric($k))
                {
                    $insert_sets[] = "{$v}";
                }
                else
                {
                    $insert_sets[] = "`{$k}`=?";
                    $insert_vals[] = $v;
                }
            }

            foreach ($insert_data as $k => $v)
            {
                if (is_numeric($k))
                {
                    $insert_sets[] = "{$v}";
                }
                else
                {
                    $insert_sets[] = "`{$k}`=?";
                    $insert_vals[] = $v;
                }
            }


            $insert_set_strs = join(',', $insert_sets);

            $insert_sql = "insert into {$this->table} set {$insert_set_strs} ";

            try
            {
                $res_m->effectRowsCnt = $this->wellMsgExecute($insert_sql, $insert_vals)->rowCount();
                $res_m->pkVal         = $res_m->effectRowsCnt > 0 ? $this->getLastInsertId() : false;
            } catch (Exception $e)
            {
                $res_m->resultPath[] = 'try_insert_fail';
                $res_m->exceptionMsg = $e->getMessage();
                if ($is_need_check === true)
                {
                    if ($update_exist)
                    {
                        $res_m->resultPath[] = 'update_after_insert_fail';

                        $update_set_strs      = join(',', $update_sets);
                        $update_sql           = "update {$this->table} set {$update_set_strs} where {$where_str}";
                        $res_m->effectRowsCnt = $this->wellMsgExecute($update_sql, $update_vals)->rowCount();
                    }
                    else
                    {
                        $res_m->resultPath[] = 'getPk_after_insert_fail';
                        $res_m->pkVal        = $this->getWellMsgColumn("select {$this->pk} from {$this->table} where {$where_str}", $where_vals);
                    }
                }
                else
                {
                    $res_m->resultPath[] = 'do_nothing_after_insert_fail';
                }
            }
        }
        else
        {

            if ($update_exist === true)
            {
                if (count($update_sets) > 0)
                {
                    $res_m->resultPath[]           = 'try_update_after_exist';
                    $res_m->isTryUpdateed          = true;
                    $update_set_strs               = join(',', $update_sets);
                    $update_sql                    = "update {$this->table} set {$update_set_strs} where {$where_str}";
                    $res_m->effectRowsCnt          = $this->wellMsgExecute($update_sql, $update_vals)->rowCount();
                    $res_m->debugData['tryUpdate'] = [
                        $update_sql,
                        $update_vals
                    ];
                }
                else
                {
                    $res_m->isTryUpdateed = false;
                    $res_m->resultPath[]  = 'no_update_data_after_exist';
                }

            }
            else
            {
                $res_m->resultPath[] = 'do_nothing_after_exist';
            }
        }

        return $res_m;
        // gf_shell_echo(var_export([$sql,$insert_vals,$res],true));


    }

    /**
     * @throws Exception
     */
    public function wellMsgExecute($sql, $binds = [], $execute_model = null)
    {
        try
        {
            return $this->execute($sql, $binds);
        } catch (Exception $e)
        {
            if (is_null($execute_model))
            {
                var_dump($sql, $binds, $e->getMessage());
            }
            else
            {
                $execute_model->exceptionMsg = $e->getMessage();
            }

            throw $e;
        }

    }


    /**
     * @param        $attrs
     * <br>[ 'id'=>1 ,'sta>0'=>false ,'sta2 in ('xxx') '=>false ]  转化为   where id=1 and sta>0 and sta2 in ('xxxx')
     * <br>[ 'id'=>1 ,'sta>0' ,'sta2 in ('xxx') ' ]  转化为   where id=1 and sta>0 and sta2 in ('xxxx')
     * <br> 1233  转化为 where {$this->pk}=1233
     * @param string $string
     *
     * @return array
     */
    public function getSqlAndValsFromAttrs($attrs, string $string = ' and '): array
    {

        $sqls = [];
        $vals = [];
        if (is_int($attrs) || is_string($attrs))
        {
            $sqls[] = "{$this->pk}=?";
            $vals[] = $attrs;
        }
        else
        {
            foreach ($attrs as $k => $v)
            {
                if ($v === false)
                {
                    $sqls[] = "{$k}";
                }
                else if (is_numeric($k))
                {
                    $sqls[] = "{$v}";
                }
                else
                {
                    $sqls[] = "`{$k}`=?";
                    $vals[] = $v;
                }
            }
        }

        return [join($string, $sqls), $vals];
    }

    /**
     * @param        $where
     * <br>[ 'id'=>1 ,'sta>0'=>false ,'sta2 in ('xxx') '=>false ]  转化为   where id=1 and sta>0 and sta2 in ('xxxx')
     * <br>[ 'id'=>1 ,'sta>0' ,'sta2 in ('xxx') ' ]  转化为   where id=1 and sta>0 and sta2 in ('xxxx')
     * <br> 1233  转化为 where {$this->pk}=1233
     *
     * @param string $field
     *
     * @return false|array
     */
    public function findOne($where, string $field = '*')
    {
        list($where_sql, $vals) = $this->getSqlAndValsFromAttrs($where, ' and ');
        return $this->getRow("SELECT {$field} FROM {$this->table} WHERE {$where_sql}", $vals);
    }

    public function findAll($where, string $field = '*')
    {
        list($where_sql, $vals) = $this->getSqlAndValsFromAttrs($where, ' and ');
        $sql = "SELECT {$field} FROM {$this->table} WHERE {$where_sql}";
        if (Sys::app()->debugSql)
        {
            var_dump($sql);
        }
        return $this->getAll($sql, $vals);
    }


    /**
     * @throws Exception
     */
    public function updateOneByPk($attrs): int
    {
        if (empty($attrs[$this->pk]))
        {
            throw  new Exception("attrs必须有 pk [{$this->pk}]" . json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        $pk_val = $attrs[$this->pk];
        unset($attrs[$this->pk]);
        list($set_sql, $binds) = $this->getSqlAndValsFromAttrs($attrs, ',');
        $binds[] = $pk_val;
        $sql     = " UPDATE {$this->table} SET {$set_sql} WHERE {$this->pk}=?";
        foreach ($binds as $k => $v)
        {
            if (is_array($v))
            {
                $binds[$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($binds[$k] === '{}' && (substr($k, -1) === 's' || substr($k, -4) === 'list'))
                {
                    $binds[$k] = '[]';
                }
            }
        }
        return $this->execute($sql, $binds)->rowCount();
    }


    /**
     * @return  static
     * @throws Exception
     */
    public function makeSnapshot($source_table, $snapshot_table)
    {
        if ($this->isExistTablename($snapshot_table, true))
        {
            $this->execute("drop table {$snapshot_table}");
        }

        try
        {
            $this->execute("create table {$snapshot_table} like {$source_table}");
            $filed_rows = $this->getAll("show  columns from  {$snapshot_table};");
            $fields     = [];
            foreach ($filed_rows as $row)
            {
                $fields[] = "`{$row['Field']}`";
            }
            unset($filed_rows);

            $field_str = join(',', $fields);
            $sql       = "insert into {$snapshot_table}({$field_str})select {$field_str} from {$source_table}";
            $this->execute($sql);
            return $this;
        } catch (Exception $e)
        {
            //快照要保证数据完整，不完整的会干扰、污染
            $this->execute("drop table {$snapshot_table}");
            throw $e;
        }

    }


    /**
     * 主要是为了兼容  PartitionBaseModel::getFormatedSuffixStyle ,如果不涉及分表，返回空则富川
     * 获取格式化后的日期后缀样式
     *
     * 本方法通过索引访问内部的日期格式数组，返回与日期后缀格式对应的格式化样式。
     * 这种设计允许灵活地根据不同的日期后缀格式需求，提供相应的日期格式化字符串。
     *
     * @return string 返回格式化后的日期后缀样式字符串
     */
    public function getFormatedSuffixStyle(): string
    {
        // 根据当前设置的日期后缀格式，从日期格式数组中获取对应的格式化样式
        return $this->dateFormatKV[$this->suffixDateFormat] ?? '';
    }


    /**
     * 在执行报错时，打印友好的错误信息
     *
     * @return static
     */
    public function setWellMsgOnce()
    {
        $this->exec_opts['well_msg'] = 1;
        return $this;
    }




    /**
     * @param string $tablename
     * @param string $be_update_filed      被修改的值
     * @param array  $pk2updateFiledVal_KV pk必须是 id , int类型
     *
     * @return false|PDOStatement
     * @throws Exception
     * @link   https://juejin.cn/post/7043299133360177189
     * 参考:
     * update users
     * set job = case id
     * when 1 then 'job11'
     * when 3 then 'job13'
     * end,
     * age = case id
     * when 1 then 11
     * when 2 then 12
     * end
     * where id IN (1, 2);
     */
    public function batchUpdate(string $tablename, string $be_update_filed, array $pk2updateFiledVal_KV)
    {
        if (empty($tablename))
        {
            $tablename = $this->table;
        }
        if (count($pk2updateFiledVal_KV) === 0)
        {
            throw new Exception('调用危险方法，瞎传会出事的');
        }

        $parts = [];
        $binds = [];
        $ids   = [];
        foreach ($pk2updateFiledVal_KV as $pk_val => $update_field_val)
        {
            $parts[] = "when {$pk_val} then ?";
            $binds[] = $update_field_val;
            $ids[]   = $pk_val;
        }
        $ids_str   = join(',', $ids);
        $parts_str = join("\n", $parts);
        $sql       = "
update {$tablename}
	set `{$be_update_filed}` = case id
		{$parts_str}	    
	end
where id IN ({$ids_str});";
        return $this->wellMsgExecute($sql, $binds);
    }

    /**
     * 所有表,默认是== 想要 like ，自己写%号
     *
     * @param $keyword
     *
     * @return array
     */
    public function getTableNamesByKeyword($keyword): array
    {
        $kw   = str_replace('_', '\_', $keyword);
        $rows = $this->getAll("SHOW TABLES LIKE '{$kw}'");
        $tns  = [];
        foreach ($rows as $row)
        {
            $tns[] = array_values($row)[0];
        }
        return $tns;
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }


    public static function timeField2Size($timeField)
    {
        $lastWord = strtolower($timeField[strlen($timeField) - 1]);
        switch ($lastWord)
        {
            case 'd':
                return 'Ymd';
            case 'm':
                return 'Ym';
            case 'y':
                return 'Y';
            default:
                return false;
        }

    }

    private function _formatTablename($tablename)
    {
        $ar = explode('.', $tablename);
        foreach ($ar as $i => $str)
        {
            if (!strstr($str, '`'))
            {
                $ar[$i] = '`' . $str . '`';
            }
        }
        return join('.', $ar);
    }

    /**
     * @param string $table     表名
     * @param array  $whereData 查询条件，建议是唯一索引或者组合
     *                          <br> !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     *                          <br>这个玩意 一定不要多写，缩小查询范围，没查到，导致插入的时候报错
     *                          <br> !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * @param array  $extData   其他非查询条件的插入值
     * @param bool   $returnPK  返回 false:是否插入成功  true:插入成功时返回PK
     *
     * @return false|int
     * @throws Exception
     */
    public function tryInsertRowOnNotExist(string $table, array $whereData, array $extData = [], bool $returnPK = false)
    {
        $tn            = $this->_formatTablename($table);
        $insertData    = [];
        $insertKeys    = [];
        $insertHolders = [];
        $fullVals      = [];

        foreach ($whereData as $k => $v)
        {
            $insertData[$k]  = $v;
            $insertKeys[]    = "`{$k}`";
            $fullVals[]      = $v;
            $insertHolders[] = '?';
        }
        foreach ($extData as $k => $v)
        {
            if (!isset($insertData[$k]))
            {
                $insertData[$k]  = $v;
                $insertKeys[]    = "`{$k}`";
                $fullVals[]      = $v;
                $insertHolders[] = '?';
            }
        }
        $whereStrs = [];
        foreach ($whereData as $k => $v)
        {
            $whereStrs[] = "`{$k}`=?";
            $fullVals[]  = $v;
        }
        $insertKeysStr    = join(',', $insertKeys);
        $insertHoldersStr = join(',', $insertHolders);
        $whereStr         = join(' and ', $whereStrs);
        //$sql="INSERT INTO {$table} (group_flag, k,val)
        //SELECT 'test', 3,'{}' FROM DUAL where not exists(select 1 from {$table} where group_flag='test' and k=3);";
        $sql = "INSERT INTO {$tn} ({$insertKeysStr})
SELECT {$insertHoldersStr} FROM DUAL where not exists(select 1 from {$tn} where {$whereStr});";
        if ($this->execute($sql, $fullVals)->rowCount() > 0)
        {
            return $returnPK ? intval($this->db->lastInsertId()) : true;
        }
        else
        {
            return false;
        }
    }

    public function tryInsertRowsOnNotExist(array $dataRows, array $uniqueKeys, $jsonAttrs = ['json_data'])
    {

        $table = isset($this->execSetting['tmpTable']) && $this->execSetting['tmpTable'] ? $this->execSetting['tmpTable'] : $this->table;
        unset($this->execSetting['tmpTable']);
        /*
         INSERT INTO tmp (group_flag, k, val)
SELECT jt.group_flag, jt.k, jt.val
FROM JSON_TABLE(
    '[
    {"group_flag":"test1","k":2,"val":"{}"},
    {"group_flag":"test1","k":1,"val":"{}"},
      {"group_flag":"test2","k":2,"val":"{}"},
      {"group_flag":"test3","k":3,"val":"{}"}
        ]',
    '$[*]' COLUMNS (
        group_flag VARCHAR(255) PATH '$.group_flag',
        k INT PATH '$.k',
        val TEXT PATH '$.val'
    )
) AS jt
WHERE NOT EXISTS (
    SELECT 1 FROM tmp
    WHERE group_flag = jt.group_flag AND k = jt.k
);
         */

        /*
                 INSERT INTO tmp (group_flag, k, val)
SELECT jt.group_flag, jt.k, jt.val
FROM JSON_TABLE(
    '[
    {"group_flag":"test1","k":2,"val":"{}"},
    {"group_flag":"test1","k":1,"val":"{}"},
      {"group_flag":"test2","k":2,"val":"{}"},
       {"group_flag":"test2","k":4,"val":"{}"},
      {"group_flag":"test3","k":3,"val":"{}"}
        ]',
    '$[*]' COLUMNS (
        group_flag VARCHAR(255) PATH '$.group_flag',
        k INT PATH '$.k',
        val TEXT PATH '$.val'
    )
) AS jt left join tmp on tmp.group_flag=jt.group_flag and tmp.k=jt.k where tmp.group_flag is null;
         */

        // dt:dst_table jt:json_table
        $tn = $this->_formatTablename($table);

        $key2types       = $this->getField2DbDataTypeKV($tn);
        $key2collections = $this->getField2DbCollectionKV($tn);
        $row1            = $dataRows[0];
        $insertKeys      = [];
        $tmpTableStrs    = [];
        $tmpSelects      = [];
        $tmpWhereIsNull  = "dt.`{$uniqueKeys[0]}` is null";
        $letJoinStrs     = [];
        foreach ($row1 as $k => $v)
        {
            $insertKeys[] = "`{$k}`";
            if (in_array($k, $jsonAttrs))
            {
                $tmpSelects[] = "JSON_UNQUOTE(jt.`${k}`) as `${k}`";
            }
            else
            {
                $tmpSelects[] = "jt.`${k}`";
            }

            $collection     = empty($key2collections[$k]) ? '' : "COLLATE {$key2collections[$k]}";
            $tmpTableStrs[] = "`{$k}` {$key2types[$k]} {$collection} PATH '$.{$k}'";
        }

        foreach ($uniqueKeys as $uniqueKey)
        {
            $letJoinStrs[] = "dt.`{$uniqueKey}`=jt.`{$uniqueKey}`";
        }
        $insertKeysStr = join(',', $insertKeys);
        $tmpSelectStr  = join(',', $tmpSelects);
        $letJoinStr    = join(' and ', $letJoinStrs);
        $tmpTableStr   = join(",\n", $tmpTableStrs);
        $rowsJsonStr   = json_encode($dataRows);

        $sql = "INSERT INTO {$tn} ({$insertKeysStr})
SELECT {$tmpSelectStr} FROM JSON_TABLE(
    '{$rowsJsonStr}',
    '$[*]' COLUMNS (
        {$tmpTableStr}
    )
) AS jt left join {$tn} as dt on {$letJoinStr} where {$tmpWhereIsNull};";

        return $this->execute($sql)->rowCount();
    }


    public function updateByJsonRowsOnExist(array $dataRows, array $uniqueKeys, $updateKeys, $jsonAttrs = ['json_data'], $dstWheres = [])
    {

        $table = isset($this->execSetting['tmpTable']) && $this->execSetting['tmpTable'] ? $this->execSetting['tmpTable'] : $this->table;
        unset($this->execSetting['tmpTable']);

        /*
                 INSERT INTO tmp (group_flag, k, val)
SELECT jt.group_flag, jt.k, jt.val
FROM JSON_TABLE(
    '[
    {"group_flag":"test1","k":2,"val":"{}"},
    {"group_flag":"test1","k":1,"val":"{}"},
      {"group_flag":"test2","k":2,"val":"{}"},
       {"group_flag":"test2","k":4,"val":"{}"},
      {"group_flag":"test3","k":3,"val":"{}"}
        ]',
    '$[*]' COLUMNS (
        group_flag VARCHAR(255) PATH '$.group_flag',
        k INT PATH '$.k',
        val TEXT PATH '$.val'
    )
) AS jt left join tmp on tmp.group_flag=jt.group_flag and tmp.k=jt.k where tmp.group_flag is null;
         */

        // dt:dst_table jt:json_table
        $tn = $this->_formatTablename($table);

        $key2types         = $this->getField2DbDataTypeKV($tn);
        $key2collections   = $this->getField2DbCollectionKV($tn);
        $row1              = $dataRows[0];
        $updateSetKeys     = [];
        $tmpTableStrs      = [];
        $tmpSelects        = [];
        $tmpWhereIsNotNull = "jt.`{$uniqueKeys[0]}` is not null";
        $letJoinStrs       = [];
        foreach ($row1 as $k => $v)
        {
            if (in_array($k, $jsonAttrs))
            {
                $tmpSelects[] = "JSON_UNQUOTE(jt.`${k}`) as `${k}`";
            }
            else
            {
                $tmpSelects[] = "jt.`${k}`";
            }
            //$tmpSelects[]   = "jt.`${k}`";
            $collection     = empty($key2collections[$k]) ? '' : "COLLATE {$key2collections[$k]}";
            $tmpTableStrs[] = "`{$k}` {$key2types[$k]} {$collection} PATH '$.{$k}'";
        }
        foreach ($updateKeys as $k)
        {
            $updateSetKeys[] = "dt.`{$k}`=jt.`{$k}`";
        }

        foreach ($uniqueKeys as $uniqueKey)
        {
            $letJoinStrs[] = "dt.`{$uniqueKey}`=jt.`{$uniqueKey}`";
        }
        $updateStr    = join(',', $updateSetKeys);
        $tmpSelectStr = join(',', $tmpSelects);
        $letJoinStr   = join(' and ', $letJoinStrs);
        $tmpTableStr  = join(",\n", $tmpTableStrs);
        $rowsJsonStr  = json_encode($dataRows);
        $dstWhereStrs = [];
        foreach ($dstWheres as $k => $v)
        {
            $dstWhereStrs[] = "dt.`{$k}`={$v} and ";
        }
        $dstWhereStr = join('', $dstWhereStrs);
        $sql         = "update {$tn} as dt
    left join JSON_TABLE(
    '{$rowsJsonStr}',
    '$[*]' COLUMNS (
        {$tmpTableStr}
    )
) AS jt on {$letJoinStr} set {$updateStr} where {$dstWhereStr} {$tmpWhereIsNotNull};";

        return $this->execute($sql)->rowCount();
    }

    public function initTableInfo($tablename)
    {
        $tn = $this->_formatTablename($tablename);

        if (!isset($this->tablename2infoKV[$tn]))
        {

            $colums              = $this->getAll("show full columns from {$tn};");
            $colsMap             = [];
            $field2type_KV       = [];
            $field2collection_KV = [];
            foreach ($colums as $col)
            {
                $colsMap[$col['Field']]             = $col;
                $field2type_KV[$col['Field']]       = $col['Type'];
                $field2collection_KV[$col['Field']] = $col['Collation'];
            }
            $this->tablename2infoKV[$tn] = [
                'tpl' => [],
                'col' => [
                    'info'        => $colsMap,
                    'dbDatatype'  => $field2type_KV,
                    'dbCollation' => $field2collection_KV,
                ],
            ];
        }
        return $this;
    }

    public function getField2ColInfoKV($tablename)
    {
        $this->initTableInfo($tablename);
        return $this->tablename2infoKV[$tablename]['col']['info'];
    }

    public function getField2DbDataTypeKV($tablename)
    {
        $this->initTableInfo($tablename);
        return $this->tablename2infoKV[$tablename]['col']['dbDatatype'];
    }

    public function getField2DbCollectionKV($tablename)
    {
        $this->initTableInfo($tablename);
        return $this->tablename2infoKV[$tablename]['col']['dbCollation'];

    }

    public function insert($table, $columns)
    {
        $sql = " INSERT INTO {$table} (`" . implode('`, `', array_keys($columns));
        $sql .= '`) VALUES (' . $this->questionMarks(count($columns)) . ')';
        // Now the query should be as follows:
        // INSERT INTO table (c1, c2, c3) VALUES (?, ?, ?)
        $res = $this->execute($sql, array_values($columns))->rowCount();
        if ($res > 0)
        {
            return $this->db->lastInsertId();
        }
        else
        {
            return FALSE;
        }
    }

    public function tryInsert($table, $columns, $duplicate_columns = [], $update_columns = [])
    {
        $insert_sets = [];
        $insert_vals = [];
        foreach ($columns as $k => $v)
        {
            $insert_sets[] = "`{$k}`=?";
            $insert_vals[] = $v;
        }
        // $sql = " INSERT ignore into INTO {$table} (`" . implode('`, `', array_keys($columns));
        // $sql .= '`) VALUES (' . $this->questionMarks(count($columns)) . ')';
        $sql = "insert ignore into {$table} set " . join(',', $insert_sets);
        // Now the query should be as follows:
        // INSERT INTO table (c1, c2, c3) VALUES (?, ?, ?)
        $res = $this->execute($sql, $insert_vals)->rowCount();
        // gf_shell_echo(var_export([$sql,$insert_vals,$res],true));

        if ($res > 0)
        {
            return $this->db->lastInsertId();
        }
        else
        {
            if (count($duplicate_columns) > 0)
            {
                if (count($update_columns) > 0)
                {
                    return $this->update($table, $update_columns, $duplicate_columns);
                }
                else
                {
                    $wheres = [];
                    $vals   = [];
                    foreach ($duplicate_columns as $k => $v)
                    {
                        $wheres[] = "`{$k}`=?";
                        $vals[]   = $v;
                    }
                    $wheres_str = join(' and ', $wheres);
                    return intval($this->getRow("select id from {$table} where {$wheres_str}", $vals)) > 0;
                }
            }
            return FALSE;
        }
    }


    public function update($table, $param, $where, $conjunction = 'AND')
    {
        if (!count($param))
        {
            $this->errMessage = 'update must have set.';
            throw new SysException('update must have set.');
        }
        if (!count($where))
        {
            $this->errMessage = 'update must have where.';
            throw new SysException('update must have where.');
        }
        $whereValues = array();
        $sql         = " UPDATE $table SET " . $this->makeSetSQL($param) . ' WHERE ' . $this->makeWhereSQL($where, $conjunction, $whereValues);
        return $this->execute($sql, array_merge(array_values($param), $whereValues))->rowCount();
    }

    public function delete($table, $where, $conjunction = 'AND')
    {
        if (!count($where))
        {
            $this->errMessage = 'delete must have where.';
            throw new SysException('delete must have where.');
        }
        $whereValues = array();
        $sql         = " DELETE FROM $table WHERE " . $this->makeWhereSQL($where, $conjunction, $whereValues);
        return $this->execute($sql, $whereValues)->rowCount();
    }


    public function makeSetSQL($columns)
    {
        if (!count($columns))
        {
            throw new SysException ('columns must not be empty');
        }
        $tmp = array();
        // Same syntax works for NULL as well.
        foreach ($columns as $col => $val)
        {
            $tmp[] = "`${col}`=?";
        }
        return implode(', ', $tmp);
    }

    public function makeWhereSQL($where_columns, $conjunction = 'AND', &$params = array())
    {
        if (!in_array(strtoupper($conjunction), array('AND', '&&', 'OR', '||', 'XOR')))
        {
            throw new SysException ('conjunction' . $conjunction . 'invalid operator');
        }
        if (!count($where_columns))
        {
            return '1';
        }
        $tmp = array();
        foreach ($where_columns as $colName => $colValue)
        {
            $col = implode('.', array_map('gf_add_sql_quote', explode('.', $colName)));
            if ($colValue === NULL)
            {
                $tmp[] = "$col IS NULL";
            }
            else if (is_array($colValue))
            {
                if (empty($colValue))
                {
                    $tmp[] = '1=0';
                }
                else
                {
                    // Suppress any string keys to keep array_merge() from overwriting.
                    $params = array_merge($params, array_values($colValue));
                    $tmp[]  = sprintf('%s IN(%s)', $col, $this->questionMarks(count($colValue)));
                }
            }
            else
            {
                $tmp[]    = "${col}=?";
                $params[] = $colValue;
            }
        }
        return implode(" ${conjunction} ", $tmp);
    }

    public function makeOrderBy($orders)
    {
        if (empty($orders))
        {
            return '';
        }
        $ret = ' ORDER BY ';
        foreach ($orders as $f => $a)
        {
            $ret .= $f . ' ' . ($a == 1 || $a == 'asc' ? 'ASC' : 'DESC') . ',';
        }
        return substr($ret, 0, -1);
    }

    public function questionMarks($count)
    {
        if ($count <= 0)
        {
            throw new SysException('count must be greater than zero');
        }
        return implode(', ', array_fill(0, $count, '?'));
    }

    /** @deprecated  准备废弃 */
    public function setDbLink($pdo = false)
    {
        return $this->connect($pdo);
    }

    /** @deprecated  准备废弃 */
    public function getDbLink()
    {
        return $this->db;
    }


}

class DbDataExecRes
{
    public $isTryChcked   = false;
    public $isTryInserted = false;
    public $isTryUpdateed = false;
    public $effectRowsCnt = 0;
    public $isExist       = false;
    public $pkVal         = false;
    public $exceptionMsg  = '';
    public $debugData     = [];
    public $resultPath    = [];
}