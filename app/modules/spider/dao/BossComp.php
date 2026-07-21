<?php

namespace modules\spider\dao;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @date 2026/07/01 17:36:34
 * @author ahyjl@126.com
 * @example
 * @link
 * @desc
 * Class BossComp
 * @property int id (int unsigned)
 * @property string com_id (varchar(64))
 * @property string title (varchar(64))
 * @property int is_ok (tinyint)这个公司行不行？
 * @property string detail (longtext)
 * @property string remark (longtext)
 * @property int is_self_biz (tinyint)
 * @property int spider_times (bigint)出现次数，用于观察boss 如何畜生
 * @property string create_date (datetime)
 * @property string update_time (datetime)
 */
class BossComp extends DbModel
{
    protected $allAttrKeys = ['id', 'com_id', 'title', 'is_ok', 'detail', 'remark', 'is_self_biz', 'spider_times', 'create_date', 'update_time'];
    const fields = '`id`,`com_id`,`title`,`is_ok`,`detail`,`remark`,`is_self_biz`,`spider_times`,`create_date`,`update_time`';


    const tableName = 'boss_comp';

    public function getTableName(): string
    {
        return self::tableName;
    }

    /**
     * @throws \Exception
     */
    public function getConnection(): \hammer\db\MysqlPdo
    {
        return Sys::app()->db('spider');
    }

    public function getOpenInfo()
    {
        return [

            'id'           => $this->id,
            'com_id'       => $this->com_id,
            'title'        => $this->title,
            'is_ok'        => $this->is_ok,
            'detail'       => $this->detail,
            'remark'       => $this->remark,
            'is_self_biz'  => $this->is_self_biz,
            'spider_times' => $this->spider_times,
            'create_date'  => $this->create_date,
            'update_time'  => $this->update_time,

        ];
    }
}