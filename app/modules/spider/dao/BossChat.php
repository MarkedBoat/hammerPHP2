<?php

namespace modules\spider\dao;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @date 2026/07/08 14:50:04
 * @author ahyjl@126.com
 * @example
 * @link
 * @desc
 * Class BossChat
 * @property int id (int unsigned)
 * @property string com_title (varchar(128))
 * @property string hr_title (varchar(32))
 * @property string job_title (varchar(64))
 * @property string last_chat_content (json)最后聊天内容
 * @property string create_date (datetime)
 * @property string update_date (datetime)
 */
class BossChat extends DbModel
{
    protected $allAttrKeys = ['id', 'com_title', 'hr_title', 'job_title', 'last_chat_content', 'create_date', 'update_date'];
    const fields = '`id`,`com_title`,`hr_title`,`job_title`,`last_chat_content`,`create_date`,`update_date`';


    const tableName = 'boss_chat';

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

            'id'                => $this->id,
            'com_title'         => $this->com_title,
            'hr_title'          => $this->hr_title,
            'job_title'         => $this->job_title,
            'last_chat_content' => $this->last_chat_content,
            'create_date'       => $this->create_date,
            'update_date'       => $this->update_date,

        ];
    }
}