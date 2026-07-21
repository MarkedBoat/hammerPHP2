<?php

namespace modules\spider\dao;

use hammer\db\DbModel;
use hammer\sys\Sys;

/**
 * @date 2026/06/03 14:38:40
 * @author ahyjl@126.com
 * @example
 * @link
 * @desc
 * Class BossImgOcr
 * @property int id
 * @property int img_id 第一个出现的图片id
 * @property string img_md5 图片md5
 * @property string ocr_text ocr 识别结果
 * @property string fixed_text 矫正结果
 * @property int is_ok 这个行不行  1:ok 2:错误
 * @property string create_date
 * @property string update_date
 */
class BossImgOcr extends DbModel
{
    protected $allAttrKeys = ['id', 'img_id', 'img_md5', 'ocr_text', 'fixed_text', 'is_ok', 'create_date', 'update_date'];
    const fields = '`id`,`img_id`,`img_md5`,`ocr_text`,`fixed_text`,`is_ok`,`create_date`,`update_date`';


    const tableName = 'boss_img_ocr';

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

            'id'          => $this->id,
            'img_id'      => $this->img_id,
            'img_md5'     => $this->img_md5,
            'ocr_text'    => $this->ocr_text,
            'fixed_text'  => $this->fixed_text,
            'is_ok'       => $this->is_ok,
            'create_date' => $this->create_date,
            'update_date' => $this->update_date,

        ];
    }
}