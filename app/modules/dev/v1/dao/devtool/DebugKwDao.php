<?php

namespace modules\dev\v1\dao\devtool;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @property int id
 * @property string domain_prefix 项目域名前缀
 * @property int ver 版本
 * @property string api_name api名
 * @property string args_json 参数
 * @property string title 标题
 * @property string brief 简介
 * @property int isdel 1:删除  2:正常
 * @property string create_at
 * @property string update_at
 */
class DebugKwDao extends DbModel
{

    const tableName = 'dev_runner_debug_kws';
    const fields    = '`id`,`domain_prefix`,`ver`,`api_name`,`args_json`,`title`,`brief`,`isdel`,`create_at`,`update_at`';

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
            'id'        => $this->id,
            'args_json' => json_decode($this->args_json),
            'title'     => $this->title,
            'brief'     => $this->brief,
        ];
    }


}