<?php

namespace hammer\db;

use hammer\db\old\DbData;
use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\HttpApp;
use modules\spider\dao\BossComp;

class DbQuery
{

    private $datamodel;
    private $tableName         = '';
    private $whereAttributes   = [];
    private $selectFields      = [];
    private $orderByfiled2type = [];
    private $limit             = [];
    private $groupByFields     = [];
    private $havingConditions  = [];
    private $alias             = '';
    private $leftJoins         = [];
    private $returnOptKV       = [];
    private $runTimeCache      = [];

    private $gte_ymd = 0;
    private $lte_ymd = 0;

    private $sym_map  = [
        '$gt'  => '>',
        '$gte' => '>=',
        '$lt'  => '<',
        '$lte' => '<=',
        '$eq'  => '=',
    ];
    private $sort_map = [
        -1 => 'desc',
        1  => 'asc',
    ];

    public function __construct(DbModel $datamodel, $gte_ymds = 0, $lte_ymd = 0)
    {
        $this->gte_ymd   = $gte_ymds;
        $this->lte_ymd   = $lte_ymd;
        $this->datamodel = $datamodel;

    }

    public static function m(DbModel $dataModel)
    {
        return new static($dataModel);
    }


    /**
     * @param DbModel $datamodel
     * @return static
     */
    public function setDataModel(DbModel $datamodel)
    {
        $this->datamodel = $datamodel;
        return $this;
    }

    /**
     * @param $tablename
     * @return static
     */
    public function setTableName($tablename)
    {
        $this->tableName = $tablename;
        unset($this->runTimeCache['sql_str_table_name']);

        return $this;
    }

    /**
     * 设置条件，采用mongo 类型
     * @param array $whereAttributes
     * <br>:[ 'id'=>[ $in=>[1,2,3] ]  ]
     * <br>:[ 'id'=>[1,2,3] ,'id2'=>["$gte"=>1]   ]
     * <br>:[ 'id in (1,2,3)' ]
     * @return static
     */
    public function setWheres($whereAttributes)
    {
        $this->whereAttributes = $whereAttributes;
        unset($this->runTimeCache['sql_str_where']);
        return $this;
    }

    /**
     * @param array $selectFields
     * <br>:[]
     * <br>:[*]
     * <br>: [a,b,c]
     * <br>:{ as_key:source_filed,  as_key:source_function(xxx) }
     * @return static
     */
    public function setSelects($selectFields)
    {
        $this->selectFields = $selectFields;
        unset($this->runTimeCache['sql_str_select_fields']);

        return $this;
    }

    /**
     * @param array $orderByfiled2type ['id'=>'desc/-1','id2'=>'asc/1']
     * @return static
     */
    public function setOrderFiled2Type($orderByfiled2type)
    {
        $this->orderByfiled2type = $orderByfiled2type;
        unset($this->runTimeCache['sql_str_sort']);
        return $this;
    }

    /**
     * @param $pageIndex
     * @param $pageSize
     * @return static
     */
    public function setLimit($pageIndex, $pageSize)
    {
        $pageIndex   = intval($pageIndex);
        $this->limit = ['page_index' => ($pageIndex > 0 ? $pageIndex : 1) - 1, 'page_size' => $pageSize];
        unset($this->runTimeCache['sql_str_limit']);
        return $this;
    }

    /**
     * @param $groupByFields
     * @return static
     */
    public function setGroupByFields($groupByFields)
    {
        $this->groupByFields = $groupByFields;
        unset($this->runTimeCache['sql_str_group_by']);

        return $this;
    }

    /**
     * @param $havingConditions
     * @return static
     */
    public function setHavingConditions($havingConditions)
    {
        $this->havingConditions = $havingConditions;
        unset($this->runTimeCache['sql_str_having']);

        return $this;
    }

    /**
     * @param $alias
     * @return static
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        unset($this->runTimeCache['sql_str_main_table_alias']);

        return $this;
    }

    /**
     * @param string $extTablename
     * @param string $alias 主表别名
     * @param string $extTableField
     * @param string $mainTableField
     * @return static
     */
    public function leftJoin($extTablename, $alias, $extTableField, $mainTableField)
    {
        $this->leftJoins[$alias] = ['extTn' => $extTablename, 'alias' => $alias, 'extField' => $extTableField, 'mainField' => $mainTableField];
        return $this;
    }

    public function setInputDataBox(DataBox $dataBox)
    {
        $attrs = $dataBox->tryGetArray('attr');
        if (count($attrs))
        {
            $this->setWheres($attrs);
        }
        $pageAr = $dataBox->tryGetArray('page');
        if (count($pageAr))
        {
            $page = new DataBox($pageAr);

            $pageIndex = $page->defaulVal(1)->tryGetInt('no');
            $pageSize  = $page->defaulVal(10)->tryGetInt('size');
            $this->setLimit($pageIndex, $pageSize);
        }
        else
        {
            $this->setLimit(1, 10);
        }
        $this->setOrderFiled2Type($dataBox->tryGetArray('sort'));
        return $this;
    }

    /**
     * @return static
     */
    public function clear()
    {
        $this->whereAttributes   = [];
        $this->selectFields      = [];
        $this->orderByfiled2type = [];
        $this->limit             = [];
        $this->groupByFields     = [];
        $this->havingConditions  = [];
        $this->alias             = '';
        $this->leftJoins         = [];
        $this->runTimeCache      = [];
        return $this;
    }

    /**
     * @return array
     */
    public function makeSqlAndBind(): array
    {
        if (empty($this->runTimeCache['sql_str_main_table_pk']) || empty($this->runTimeCache['sql_str_table_name']) || empty($this->runTimeCache['sql_str_main_table_alias']))
        {
            $mainTableType = '';
            //        $mainTableType = $this->datamodel->getFormatedSuffixStyle();

            $mainTn2alias = [];
            if ($mainTableType === '')
            {

            }
            else
            {

            }
            $tableName = $this->tableName ? $this->tableName : $this->datamodel->getTableName();

            $mainAlias = $tableName;
            $mainPk    = $this->datamodel->getPkField();
            if ($this->alias)
            {
                $tableName = "{$tableName} as {$this->alias}";
                $mainAlias = $this->alias;
            }


            if (count($this->leftJoins))
            {
                foreach ($this->leftJoins as $extAlias => $leftJoin)
                {
                    $tableName .= " left join {$leftJoin['extTn']} as {$extAlias} on {$mainAlias}.{$leftJoin['mainField']}={$extAlias}.{$leftJoin['extField']}";
                }
            }

            $this->runTimeCache['sql_str_main_table_pk']    = $mainPk;
            $this->runTimeCache['sql_str_table_name']       = $tableName;
            $this->runTimeCache['sql_str_main_table_alias'] = $mainAlias;
        }

        if (empty($this->runTimeCache['sql_str_where']) || empty($this->runTimeCache['sql_str_binds']))
        {
            $cs    = [];
            $binds = [];
            foreach ($this->whereAttributes as $field => $val)
            {
                if (is_numeric($field) && is_string($val))
                {
                    $cs[] = $val;
                }
                else if (is_array($val))
                {
                    $field = strstr($field, '.') ? $field : "`{$field}`";
                    if (isset($val[0]))
                    {
                        $cs2 = [];
                        foreach ($val as $v)
                        {
                            $binds[] = $v;
                            $cs2[]   = '?';
                        }
                        $cs[] = " {$field} in (" . join(',', $cs2) . ")";
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
                                $cs[] = " {$field} in (" . join(',', $cs2) . ")";
                            }
                            else if ($mongo_type === '$regex' || $mongo_type === '$like')
                            {
                                //mongo 的 is null 和 is not null 比较复杂，先借用like
                                if ($v2 === 'is null')
                                {
                                    $cs[] = "{$field} is null";
                                }
                                else if ($v2 === 'is not null')
                                {
                                    $cs[] = "{$field} is not null";
                                }
                                else
                                {
                                    $cs[]    = "{$field} like ?";
                                    $binds[] = "%{$v2}%";
                                }
                            }
                            else
                            {
                                if (isset($this->sym_map[$mongo_type]))
                                {
                                    $cs[]    = "{$field} {$this->sym_map[$mongo_type]}?";
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
                    if (strstr($field, '.'))
                    {
                        $cs[] = "{$field}=?";
                    }
                    else
                    {
                        $cs[] = "`{$field}`=?";
                    }

                    $binds[] = $val;
                }
            }
            $this->runTimeCache['sql_str_where'] = count($cs) === 0 ? '' : (' where ' . join(' and ', $cs));
            $this->runTimeCache['sql_str_binds'] = $binds;
        }
        if (empty($this->runTimeCache['sql_str_sort']))
        {
            $sort_strs = [];
            foreach ($this->orderByfiled2type as $filed => $sort_type)
            {
                $field_code = strstr($filed, '+0') ? "{$filed}" : "`{$filed}`";
                if (in_array($sort_type, ['asc', 'desc']))
                {
                    $sort_strs[] = "{$field_code} {$sort_type}";
                }
                else if (isset($this->sort_map[$sort_type]))
                {
                    $sort_strs[] = "{$field_code} {$this->sort_map[$sort_type]}";
                }
                else
                {
                    die("这是什么玩意? sort_type:{$sort_type}");
                }
            }
            $this->runTimeCache['sql_str_sort'] = count($sort_strs) === 0 ? '' : (' order by ' . join(',', $sort_strs));
        }
        if (empty($this->runTimeCache['sql_str_limit']))
        {
            $limit_strs = [];
            if (isset($this->limit['page_size']))
            {
                $page_size = intval($this->limit['page_size']);
                if ($page_size > 0)
                {
                    if (isset($this->limit['page_index']))
                    {
                        $limit_strs[] = ($this->limit['page_index']) * $page_size;
                    }
                    else
                    {
                        $limit_strs[] = 0;
                    }
                    $limit_strs[] = $page_size;
                }
            }
            $this->runTimeCache['sql_str_limit'] = count($limit_strs) === 0 ? '' : (' limit ' . join(',', $limit_strs));
        }
        if (empty($this->runTimeCache['sql_str_select_fields']))
        {
            $filed_str = '';
            if (count($this->selectFields) === 0)
            {
                $filed_str = '*';
            }
            else
            {
                $ar = [];
                foreach ($this->selectFields as $as_field => $raw_field_str)
                {
                    $as = is_numeric($as_field) ? '' : " as `{$as_field}`";
                    if (is_string($raw_field_str))
                    {
                        if (strstr($raw_field_str, '(') || strstr($raw_field_str, '.'))
                        {
                            $ar[] = "{$raw_field_str}  {$as}";
                        }
                        else
                        {
                            $ar[] = "`{$raw_field_str}`  {$as}";
                        }
                    }
                    else if (is_array($raw_field_str))
                    {
                        //[type,code,default_value]
                        if ($raw_field_str[0] === 'json')
                        {
                            //[json,field,path]
                            $ar[] = "json_extract(`{$raw_field_str[1]}`,'$.{$raw_field_str[2]}')  {$as}";
                        }
                        else
                        {
                            die("这是什么玩意? select_field:{$raw_field_str[0]}");
                        }
                    }

                }
                $filed_str = join(',', $ar);
            }
            $this->runTimeCache['sql_str_select_fields'] = $filed_str;
        }

        if (empty($this->runTimeCache['sql_str_group_by']))
        {
            $this->runTimeCache['sql_str_group_by'] = count($this->groupByFields) === 0 ? '' : (' group by  ' . join(',', $this->groupByFields));
        }
        if (empty($this->runTimeCache['sql_str_having']))
        {
            $this->runTimeCache['sql_str_having'] = count($this->havingConditions) === 0 ? '' : (' having ' . join(',', $this->havingConditions));
        }
        //   var_dump($this->runTimeCache);
        return [
            "select {$this->runTimeCache['sql_str_select_fields']} from {$this->runTimeCache['sql_str_table_name']}      {$this->runTimeCache['sql_str_where']}     {$this->runTimeCache['sql_str_group_by']} {$this->runTimeCache['sql_str_having']} {$this->runTimeCache['sql_str_sort']}  {$this->runTimeCache['sql_str_limit']}",
            $this->runTimeCache['sql_str_binds'],
            "select count({$this->runTimeCache['sql_str_main_table_alias']}.{$this->runTimeCache['sql_str_main_table_pk']}) from {$this->runTimeCache['sql_str_table_name']}  {$this->runTimeCache['sql_str_where']} {$this->runTimeCache['sql_str_group_by']} {$this->runTimeCache['sql_str_having']}",

        ];
    }


    public function queryRows()
    {
        list($sql, $binds, $count_sql) = $this->makeSqlAndBind();
        //var_dump($this->selectFields);
        //var_dump($this->whereAttributes);
        //var_dump($sql);
        if (1)
        {
            //var_dump($sql);
            //die;
            //echo "\n{$sql}\n\n\n#\n";
        }
        return DbData::m()->connectPDO($this->datamodel->getConnection())->getAll($sql, $binds);
    }

    public function queryRow()
    {
        list($sql, $binds, $count_sql) = $this->makeSqlAndBind();
        //var_dump($this->selectFields);
        //var_dump($this->whereAttributes);
        //var_dump($sql);
        if (1)
        {
            //var_dump($sql);
            //die;
            //echo "\n{$sql}\n\n\n#\n";
        }
        return $this->datamodel->getRow($sql, $binds);
    }

    public function queryScalar()
    {
        list($sql, $binds, $count_sql) = $this->makeSqlAndBind();
        //var_dump($this->selectFields);
        //var_dump($this->whereAttributes);
        //var_dump($sql);
        if (1)
        {
            //var_dump($sql);
            //die;
            //echo "\n{$sql}\n\n\n#\n";
        }
        return $this->datamodel->getColumn($sql, $binds);
    }


    /**
     * 是否直接返回 ajax
     *  是否返回标准页面查询ajax，默认值 直接修改 queryPageData方法
     * @param bool $sta
     * @return $this
     */
    public function setDirectlyReturnAjax(bool $sta)
    {
        $this->returnOptKV['returnAjax'] = $sta;
        return $this;
    }

    /**
     * 有些row的一些字段  是需要被 json_decode 为 array 的,优先级没有 object的高
     * jsonDecodeAsArrayAttrs array 默认 ['json_data'] 要被json解码的attr
     * @param array $attrs
     * @return $this
     */
    public function setQueryJsonDecodeAsArrayAttrs(array $attrs)
    {
        $this->returnOptKV['jsonDecodeAsArrayAttrs'] = $attrs;
        return $this;
    }

    /**
     * 有些row的一些字段  是需要被 json_decode 为 object 的
     * jsonDecodeAsObjectAttrs array 默认 [] 要被json解码成Object的attr
     * @param array $attrs
     * @return $this
     */
    public function setQueryJsonDecodeAsjsonDecodeAsObjectAttrs(array $attrs)
    {
        $this->returnOptKV['jsonDecodeAsObjectAttrs'] = $attrs;
        return $this;
    }

    /**
     * @return array|void
     */
    public function queryPageData()
    {
        $returnStandardAjax = $this->returnOptKV['returnAjax'] ?? false;//是否直接返回 标准数据
        $decodeObjectAttrs  = $this->returnOptKV['jsonDecodeAsObjectAttrs'] ?? [];
        $decodeArrayAttrs   = $this->returnOptKV['jsonDecodeAsArrayAttrs'] ?? [];
        $decodeArrayAttrs   = array_diff($decodeArrayAttrs, $decodeObjectAttrs);//object 优先级更高
        list($sql, $binds, $count_sql) = $this->makeSqlAndBind();
        $debug_binds = array_map(function ($v) { return is_numeric($v) ? $v : "'{$v}'"; }, $binds);


        $ar = explode('?', $sql);
        if ((count($ar) - count($debug_binds)) === 1)
        {

            foreach ($debug_binds as $i => $v)
            {
                $ar[$i] .= $v;
            }
        }
        $list_sql_replace = join('', $ar);

        $ar = explode('?', $count_sql);
        if ((count($ar) - count($debug_binds)) === 1)
        {

            foreach ($debug_binds as $i => $v)
            {
                $ar[$i] .= $v;
            }
        }
        $count_sql_replace = join('', $ar);

        $this->returnOptKV = [];
        //  die($list_sql_replace);
        if ($returnStandardAjax)
        {
            Sys::app()->logData(['bind' => $binds], 'binds');
            Sys::app()->getDispatcher()->gf_ajax_success(array_map(function ($v) use ($decodeArrayAttrs, $decodeObjectAttrs)
            {
                foreach ($decodeArrayAttrs as $jsonStringAttr)
                {
                    if (isset($v[$jsonStringAttr]))
                        $v[$jsonStringAttr] = json_decode($v[$jsonStringAttr], true);
                }
                foreach ($decodeObjectAttrs as $objectStringAttr)
                {
                    if (isset($v[$objectStringAttr]))
                        $v[$objectStringAttr] = json_decode($v[$objectStringAttr]);
                }
                return $v;
            }, $this->datamodel->getConnection()->getOldPdoCmd()->queryRows($sql, $binds)), [
                'count'      => $this->datamodel->getConnection()->getOldPdoCmd()->queryScalar($count_sql, $binds),
                'page_index' => $this->limit['page_index'],
                'page_no'    => $this->limit['page_index'] + 1,
                'page_size'  => $this->limit['page_size'],
                '_debug'     => [
                    'list_sql'          => $sql,
                    'count_sql'         => $count_sql,
                    'binds'             => $binds,
                    'list_sql_replace'  => $list_sql_replace,
                    'count_sql_replace' => $count_sql_replace,
                    'selects'           => $this->selectFields,
                    'wheres'            => $this->whereAttributes,
                    'binds2'            => $debug_binds,
                ]
            ]);

        }
        else
        {
            return [
                    'data'       => array_map(function ($v) use ($decodeArrayAttrs, $decodeObjectAttrs)
                    {
                        foreach ($decodeArrayAttrs as $jsonStringAttr)
                        {
                            if (isset($v[$jsonStringAttr]))
                                $v[$jsonStringAttr] = json_decode($v[$jsonStringAttr], true);
                        }
                        foreach ($decodeObjectAttrs as $objectStringAttr)
                        {
                            if (isset($v[$objectStringAttr]))
                                $v[$objectStringAttr] = json_decode($v[$objectStringAttr]);
                        }
                        return $v;
                    }, $this->datamodel->getConnection()->getOldPdoCmd()->queryRows($sql, $binds)),
                    'count'      => $this->datamodel->getConnection()->getOldPdoCmd()->queryScalar($count_sql, $binds),
                    'page_index' => $this->limit['page_index'],
                    'page_no'    => $this->limit['page_index'] + 1,
                    'page_size'  => $this->limit['page_size'],


                ] + (Sys::app()->isDebug() ? [
                    'list_sql'  => $sql,
                    'count_sql' => $count_sql,
                    'binds'     => $binds,
                    '_debug'    => [
                        'list_sql_replace'  => $list_sql_replace,
                        'count_sql_replace' => $count_sql_replace,
                    ]
                ] : []);
        }

    }


}