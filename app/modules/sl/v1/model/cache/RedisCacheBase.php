<?php

    namespace modules\sl\v1\model\cache;

    use hammer\sys\Sys;

    abstract class RedisCacheBase {

        public $key     = '';
        public $ttl     = 0;
        public $valType = '';
        public $map     = false;
        /**
         * @var \Redis
         */
        protected $redis = null;

        const valTypeObject = 'object';
        const valTypeArray  = 'array';
        const valTypeString = 'string';
        const valTypeInt    = 'int';
        const valTypeBool   = 'bool';

        const secsInDay   = 86400;
        const secsInWeek  = 604800;
        const secsInMonth = 2592000;

        /**
         * @var int client pk to online key
         */
        const clientPkToOnlineKey = 2;
        /**
         * @var int 客户端cid 对应 信息
         */
        const operPkToFormId = 3;

        const clientPkYmd_OlMsgSta  = '2_86400_string';//每天电视端上线的消息发送状态
        const userPk_appletFormIds  = '3_604800_array';//用户的微信小程序formId set，最长的是有效期7天
        const userPk_userAttrs      = '9_604800_array';
        const kidPk_attrs           = '10_604800_array';
        const userPk_clientPks      = '11_604800_array';//用户pk 对应的 终端id
        const clientPk_extAttrs     = '12_604800_array';//终端pk 对应的 扩展信息
        const clientPk_basicAttrs   = '13_604800_array';//终端pk 对应的 基本信息
        const client_openId_pk      = '14_2592000_int';//终端  字符串id=> 真id
        const user_openId_pk        = '15_2592000_int';//用户  字符串id=> 真id
        const userPk_kidPks         = '16_604800_array';//用户pk => [kidPk1,kidPk2]
        const clientPkYmd_7daysData = '17_604800_array';//终端pk+ymd => 7天统计
        const memberCid_packages    = '18_2592000_array';//会员cid=>套餐
        const userPk_members        = '19_2592000_array';//用户pk =>  会员情况
        const userPk_memberOpLock   = '20_60_int';      //用户pk => 会员操作锁
        const userPk_orderList      = '21_7200_array';//用户pk 订单历史
        const userPk_memberHis      = '22_7200_array';//用户pk 会员历史
        const orderPk_notify        = '23_18000_string';//订单pk 异步通知
        const ipayOrderId_orderPk   = '24_18000_int';//苹果内购订单  订单主键
        const groupPk_userPks       = '25_604800_array';//群pk  用户id
        const appletAccessToken     = '26_7000_string';
        const userPkMemberNotfiy    = '27_86400_string';
        const groupPk_members       = '28_7200_array';//群pk 成员信息
        const orderPk_attrs         = '29_7200_array';//订单pk 基本信息
        /********************************************************************************************************************************************************
         * _  _  _ _  ___      ____ _  _ _  _ ____ ___  _  ____ _  _  ___
         * |  |\ | |   |       |___ |  | |\ | |     |   |  |  | |\ | (__
         * |  | \| |   |       |    |_/| | \| |___  |   |  |__| | \| ___)
         *
         *******************************************************************************************************************************************************/

        /**
         * @param $cacheType
         * @param $key
         * @return RedisCache|RedisCacheMap
         */
        protected static function configCacheOld($cacheType, $key) {
            $model = null;
            switch ($cacheType) {
                /*
                case self::clientPkToOnlineKey:
                    $nowDate = date('Y-m-d H:i:s', time());
                    $day     = intval(substr($nowDate, 8, 2));
                    Sys::app()->interruption()->logInfo(['day', $day, $nowDate]);
                    $key   = 'sl_c_e_ol_' . $key;//client_event_online_{Id}_{date}
                    $model = self::__newModel($key, 86400, self::valTypeArray);
                    break;*/
                case self::operPkToFormId:
                    $key   = 'sl_wx_fids_' . $key;//sl_wxapplet_form_id{Id}
                    $model = self::__newModel($key, 6000, self::valTypeArray);
                    //  $model->map = 'sl_c_info_map';//client_clientId_info_map
                    break;


                case self::userPk_userAttrs:
                    $model = self::__newModel('sl_c_' . self::userPk_userAttrs . '_' . $key, self::secsInWeek, self::valTypeArray);
                    break;
                case self::kidPk_attrs:
                    $model = self::__newModel('sl_c_' . self::kidPk_attrs . '_' . $key, self::secsInWeek, self::valTypeArray);
                    break;
                case self::userPk_clientPks:
                    $model = self::__newModel('sl_c_' . self::userPk_clientPks . '_' . $key, self::secsInWeek, self::valTypeArray);
                    break;
                case self::clientPk_extAttrs:
                    $model      = self::__newModel($key, self::secsInWeek, self::valTypeArray);
                    $model->map = 'sl_c_' . self::clientPk_extAttrs;//cache_12
                    break;
                case self::clientPk_basicAttrs:
                    $model      = self::__newModel($key, self::secsInWeek, self::valTypeArray);
                    $model->map = 'sl_c_' . self::clientPk_basicAttrs;//cache_13
                    break;
                case self::client_openId_pk:
                    $model      = self::__newModel($key, self::secsInMonth, self::valTypeInt);
                    $model->map = 'sl_c_' . self::client_openId_pk;//cache_14
                    break;
                case self::user_openId_pk:
                    $model      = self::__newModel($key, self::secsInMonth, self::valTypeInt);
                    $model->map = 'sl_c_' . self::user_openId_pk;//cache_15
                    break;
                case self::userPk_kidPks:
                    $model      = self::__newModel($key, self::secsInMonth, self::valTypeArray);
                    $model->map = 'sl_c_' . self::userPk_kidPks;//cache_16
                    break;
                case self::clientPkYmd_7daysData:
                    $model = self::__newModel($key, self::secsInDay, self::valTypeArray);
                    break;

            };
            if (is_null($model))
                Sys::app()->interruption()->setCode('cacheType_not_exist')->setMsg('服务器错误')->outError();
            return $model;
        }


        /**
         * @param $cacheType string
         * @param $key
         * @return RedisCache|RedisCacheMap
         */
        protected static function configCache($cacheType, $key) {
            $model = null;
            list($cacheTypeKey, $expires, $valType) = explode('_', $cacheType);
            $expires  = intval($expires);
            $cacheKey = 'sl_c_' . $cacheTypeKey . '_' . $key;
            //Sys::app()->logData($cacheKey, $cacheType);
            $model = self::__newModel($cacheKey, $expires, $valType);
            return $model;
        }

        /**
         * RedisCacheMap 是为纯hash准备的，  RedisCache如果被hash包含的话，会代理操作
         * @param $key
         * @param $ttl
         * @param $valType
         * @return RedisCache|RedisCacheMap
         */
        private static function __newModel($key, $ttl, $valType) {
            if (is_null($key)) {
                $model          = new RedisCacheMap();
                $model->key     = false;
                $model->ttl     = $ttl;
                $model->valType = $valType;
                $model->redis   = Sys::app()->redis('sl');
                $model->redis->select(10);
                return $model;
            } else {
                $model          = new RedisCache();
                $model->key     = $key;
                $model->ttl     = $ttl;
                $model->valType = $valType;
                $model->redis   = Sys::app()->redis('sl');
                $model->redis->select(10);
                return $model;
            }
        }

        /**
         * @param $key
         * @return bool|mixed|null|string
         */
        protected function getForce($key) {
            if ($this->map === false) {
                $val = $this->redis->get($key);
            } else {
                $val = $this->redis->hGet($this->map, $key);
            }
            switch ($this->valType) {
                case self::valTypeArray:
                    $val = json_decode($val, true);
                    if (!is_array($val))
                        $val = null;
                    break;
                case self::valTypeObject:
                    $val = json_decode($val);
                    if (!is_array($val))
                        $val = null;
                    break;
                case self::valTypeInt:
                    if ($val === false)
                        $val = null; else $val = intval($val);
                    break;
                case self::valTypeBool:
                    if ($val === '1')
                        $val = true;
                    break;
                default:
                    if ($val === false)
                        $val = null;
                    break;
            }
            return $val;
        }


        protected function setForce($key, $val) {
            switch ($this->valType) {
                case self::valTypeArray:
                    $val = json_encode($val);
                    break;
                case self::valTypeObject:
                    $val = json_encode($val);
                    break;
                case self::valTypeInt:
                    $val = intval($val);
                    break;
                case self::valTypeBool:
                    if (in_array($val, [1, true, '1', 'true'], true))
                        $val = true;
                    break;
                case self::valTypeString:
                    break;
            }
            if ($this->map === false) {
                //Sys::app()->logData([$key, $val, $this->ttl]);
                $this->redis->set($key, $val, $this->ttl);
            } else {
                $this->redis->hSet($this->map, $key, $val);
            }
        }

        /**
         * @param $key
         * @return int
         */
        protected function delForce($key) {
            if ($this->map === false) {
                return $this->redis->del($key);
            } else {
                return $this->redis->hDel($this->map, $key);
            }
        }

        public function getHashMap() {
            return $this->map;
        }

    }