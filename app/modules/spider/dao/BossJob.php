<?php

namespace modules\spider\dao;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @date 2026/07/13 14:14:37
 * @author ahyjl@126.com
 * @example
 * @link
 * @desc
 * Class BossJob
 * @property int id  (int unsigned)
 * @property string com_id  (varchar(64))
 * @property string area_title  (varchar(128))
 * @property string area_code  (varchar(16))
 * @property string job_id  (varchar(64))
 * @property string title  (varchar(64))
 * @property int salary_gte 工资起点 (int)
 * @property int salary_lte 最高工资 (int)
 * @property string salary_text  (varchar(45))
 * @property string salary_ocr 薪资图片识别 (varchar(45))
 * @property string salary_shoot_md5  (varchar(32))
 * @property string tags  (json)
 * @property string deny_reason 否定这个岗位的理由 (varchar(255))
 * @property string detail 岗位本身的描述 (longtext)
 * @property string remark 备注 (longtext)
 * @property string ds_res deepseek判断结果 (json)
 * @property string spider_date 被爬时间 (datetime)
 * @property string publish_date 发布时间 (datetime)
 * @property string hr_title  (varchar(32))
 * @property string hr_active_time  (varchar(32))
 * @property int hr_active_int 活跃状态映射整数：单位编码*100+数值（1秒2分3小时4天5周6月7年），如160=60秒内，230=30分钟内，324=24小时内，403=3天内，501=本周内，502=2周内，601=本月内...607=半年前，800=未知/其他 (int)
 * @property string hr_hot_date hr最新在线时间 (datetime(3))
 * @property int is_php  (tinyint)
 * @property int is_match 不匹配 (tinyint)
 * @property int is_self_biz 自己的业务，不是外包 (tinyint)
 * @property int is_hr_live hr在活跃 (tinyint)
 * @property int is_true 信息真实，不是一眼假的
 * (tinyint)
 * @property int is_edu_ok  (tinyint)
 * @property int is_jd_ol 岗位是否在线 ，有无被下线 (tinyint)
 * @property int is_salary_ok 薪资ok (tinyint)
 * @property int is_boss_fav boss上是否标记感兴趣 (tinyint)
 * @property int fav_lev 感兴趣级别  1一点不想    2有点不想    3 不确定   4有点兴趣   5特别有兴趣 (tinyint)
 * @property int is_ds_ok  (tinyint)
 * @property string ds_deny_reason  (text)
 * @property int is_deny  (tinyint)
 * @property int is_ok  (tinyint)
 * @property string job_md5 工作唯一标识 (varchar(32))
 * @property int job_md5_cnt uniq的总数量 (smallint)
 * @property int is_md5_old 是否为老标识，Boss直聘这点真恶心 (tinyint)
 * @property string last_chat_content 最后聊天内容 (json)
 * @property int spider_times 出现次数，用于观察boss 如何畜生 (int)
 * @property int source_flag 来源 (tinyint)
 * @property string create_date  (datetime)
 * @property string update_date  (datetime)
 */
class BossJob extends DbModel
{
    protected $allAttrKeys = [
        'id',
        'com_id',
        'area_title',
        'area_code',
        'job_id',
        'title',
        'salary_gte',
        'salary_lte',
        'salary_text',
        'salary_ocr',
        'salary_shoot_md5',
        'tags',
        'deny_reason',
        'detail',
        'remark',
        'ds_res',
        'spider_date',
        'publish_date',
        'hr_title',
        'hr_active_time',
        'hr_active_int',
        'hr_hot_date',
        'is_php',
        'is_match',
        'is_self_biz',
        'is_hr_live',
        'is_true',
        'is_edu_ok',
        'is_jd_ol',
        'is_salary_ok',
        'is_boss_fav',
        'fav_lev',
        'is_ds_ok',
        'ds_deny_reason',
        'is_deny',
        'is_ok',
        'job_md5',
        'job_md5_cnt',
        'is_md5_old',
        'last_chat_content',
        'spider_times',
        'source_flag',
        'create_date',
        'update_date'
    ];
    const fields = '`id`,`com_id`,`area_title`,`area_code`,`job_id`,`title`,`salary_gte`,`salary_lte`,`salary_text`,`salary_ocr`,`salary_shoot_md5`,`tags`,`deny_reason`,`detail`,`remark`,`ds_res`,`spider_date`,`publish_date`,`hr_title`,`hr_active_time`,`hr_active_int`,`hr_hot_date`,`is_php`,`is_match`,`is_self_biz`,`is_hr_live`,`is_true`,`is_edu_ok`,`is_jd_ol`,`is_salary_ok`,`is_boss_fav`,`fav_lev`,`is_ds_ok`,`ds_deny_reason`,`is_deny`,`is_ok`,`job_md5`,`job_md5_cnt`,`is_md5_old`,`last_chat_content`,`spider_times`,`source_flag`,`create_date`,`update_date`';


    const tableName = 'boss_job';

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
            'com_id'            => $this->com_id,
            'area_title'        => $this->area_title,
            'area_code'         => $this->area_code,
            'job_id'            => $this->job_id,
            'title'             => $this->title,
            'salary_gte'        => $this->salary_gte,
            'salary_lte'        => $this->salary_lte,
            'salary_text'       => $this->salary_text,
            'salary_ocr'        => $this->salary_ocr,
            'salary_shoot_md5'  => $this->salary_shoot_md5,
            'tags'              => $this->tags,
            'deny_reason'       => $this->deny_reason,
            'detail'            => $this->detail,
            'remark'            => $this->remark,
            'ds_res'            => $this->ds_res,
            'spider_date'       => $this->spider_date,
            'publish_date'      => $this->publish_date,
            'hr_title'          => $this->hr_title,
            'hr_active_time'    => $this->hr_active_time,
            'hr_active_int'     => $this->hr_active_int,
            'hr_hot_date'       => $this->hr_hot_date,
            'is_php'            => $this->is_php,
            'is_match'          => $this->is_match,
            'is_self_biz'       => $this->is_self_biz,
            'is_hr_live'        => $this->is_hr_live,
            'is_true'           => $this->is_true,
            'is_edu_ok'         => $this->is_edu_ok,
            'is_jd_ol'          => $this->is_jd_ol,
            'is_salary_ok'      => $this->is_salary_ok,
            'is_boss_fav'       => $this->is_boss_fav,
            'fav_lev'           => $this->fav_lev,
            'is_ds_ok'          => $this->is_ds_ok,
            'ds_deny_reason'    => $this->ds_deny_reason,
            'is_deny'           => $this->is_deny,
            'is_ok'             => $this->is_ok,
            'job_md5'           => $this->job_md5,
            'job_md5_cnt'       => $this->job_md5_cnt,
            'is_md5_old'        => $this->is_md5_old,
            'last_chat_content' => $this->last_chat_content,
            'spider_times'      => $this->spider_times,
            'source_flag'       => $this->source_flag,
            'create_date'       => $this->create_date,
            'update_date'       => $this->update_date,

        ];
    }
}