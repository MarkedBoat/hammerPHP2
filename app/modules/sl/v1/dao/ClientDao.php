<?php

    namespace modules\sl\v1\dao;

    use hammer\db\DbModel;
    use hammer\sys\Sys;

    /**
     * Class Operator
     * @property int id
     * @property string str_id 字符串id
     * @property string cdate 创建时间
     * @property string mac_wireless 无线网卡地址
     * @property string mac_wired 有限网卡
     * @property string sn 电视序列号
     * @property string device_name 设备名称
     * @property string device_title 设备名  美化过的
     * @property string device_model
     * @property string ip 第一次ip
     * @property int oper 当前用户  sl_oper
     * @property string oper_open 当前操作这的openid
     * @property string conversation 当前会话
     * @property string device_type 设备类型
     * @property int mdm_id ios用的协议id
     * @property string crt_sta 证书安装状态  true:完成  false:否
     * @property string app_ver_code 终端版本
     * @property string ldate 最后时间
     */
    class ClientDao extends DbModel {

        const tableName = 'sl_client';
        const fields    = '`id`,`str_id`,`cdate`,`mac_wireless`,`mac_wired`,`sn`,`device_name`,`device_title`,`device_model`,`ip`,`oper`,`oper_open`,`conversation`,`device_type`,`mdm_id`,`crt_sta`,`app_ver_code`,`ldate`';

        const staYes = 1;
        const staNot = 2;

        public function getTableName() {
            return self::tableName;
        }


        public function getOpenInfo() {
            return [
                'clientId'   => $this->str_id,
                'createDate' => $this->cdate,
                'deviceName' => $this->device_title,
                'deviceType' => $this->device_type,
                'convId'     => $this->conversation,
            ];
        }

        public function getOpenInfoAdvance() {
            $oper = intval($this->oper);
            return [
                'clientId'   => $this->str_id,
                'createDate' => $this->cdate,
                'deviceName' => $this->device_title,
                'deviceType' => $this->device_type,
                'convId'     => $oper === 0 ? '' : $this->conversation,
                'oper'       => $oper,
                'operOpenId' => $oper === 0 ? '' : $this->oper_open,
                'mdmId'      => $this->mdm_id,
                'crtSta'     => strval($this->crt_sta) === 'true' ? true : false,
                'appVerCode' => intval($this->app_ver_code),
            ];
        }

        public static function getOpenInfoAdvanceByAttrs($attrs) {
            $oper = intval($attrs['oper']);
            return [
                'clientId'   => $attrs['str_id'],
                'createDate' => $attrs['cdate'],
                'deviceName' => $attrs['device_title'],
                'deviceType' => $attrs['device_type'],
                'convId'     => $oper === 0 ? '' : $attrs['conversation'],
                'oper'       => $oper,
                'operOpenId' => $oper === 0 ? '' : $attrs['oper_open'],
                'mdmId'      => $attrs['mdm_id'],
                'crtSta'     => strval($attrs['crt_sta']) === 'true' ? true : false,
                'appVerCode' => intval($attrs['app_ver_code']),
            ];
        }

        /**
         * @param $clientId
         * @return ClientDao
         */
        public static function findByCid($clientId) {
            $dao = ClientDao::model()->findByAttributes(['str_id' => $clientId]);
            if ($dao === false)
                Sys::app()->interruption()->setMsg('设备不存在')->setCode('client_not_exist')->outError();
            return $dao;
        }


    }