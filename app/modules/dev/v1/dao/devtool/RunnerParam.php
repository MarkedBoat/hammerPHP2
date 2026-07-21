<?php

namespace modules\dev\v1\dao\devtool;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @property int id
 * @property string domain_prefix 项目域名前缀
 * @property int ver 版本
 * @property string api_name api名
 * @property string param_key 参数数组，元素key分别为  参数名:k 参数值:val或vals  参数处理:url编码、哪种签名  备注:remark
 * @property string param_val
 * @property string param_md5
 * @property string fun 调用处理函数
 * @property string brief 简介
 * @property int isdel 1:删除  2:正常
 * @property string create_at
 * @property string update_at
 */
class RunnerParam extends DbModel
{

    const tableName = 'dev_runner_params';
    const fields = '`id`,`domain_prefix`,`ver`,`api_name`,`param_key`,`param_val`,`param_md5`,`fun`,`brief`,`isdel`,`create_at`,`update_at`';

    const staYes = 1;
    const staNot = 2;

    public function getTableName()
    {
        return self::tableName;
    }

    public function getConnection()
    {
        return Sys::app()->db('cli');
    }

    public function getOpenInfo()
    {
        return [
            'id'            => $this->id,
            'domain_prefix' => $this->domain_prefix,
            'version'       => $this->ver,
            'api_name'      => $this->api_name,
            'args_json'     => json_decode($this->params_json),
            'title'         => $this->title,
            'brief'         => $this->brief,
        ];
    }


}