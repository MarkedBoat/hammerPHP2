<?php

namespace modules\dev\v1\dao\devtool;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @property int id
 * @property string domain_prefix 项目域名前缀
 * @property int ver 版本
 * @property string api_name api名
 * @property string params_json 参数数组，元素key分别为  参数名:k 参数值:val或vals  参数处理:url编码、哪种签名  备注:remark
 * @property string params_md5 参数的防重复
 * @property string title 标题
 * @property string brief 简介
 * @property int isdel 1:删除  2:正常
 * @property string create_at
 * @property string update_at
 * @property int times
 * @property int is_display 1:显示  2:隐藏
 */
class RunnerApiHis extends DbModel
{


    const tableName = 'dev_runner_api_his';
    const fields    = '`id`,`domain_prefix`,`ver`,`api_name`,`params_json`,`params_md5`,`title`,`brief`,`isdel`,`create_at`,`update_at`,`times`,`is_display`';

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
            'is_display'    => $this->is_display,
            'create_at'     => $this->create_at,
            'update_at'     => $this->update_at,
            'times'         => $this->times,
        ];
    }


}