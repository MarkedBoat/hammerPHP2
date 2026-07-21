<?php

    namespace modules\sl\v1\model;


    use hammer\sys\Sys;
    use modules\sl\v1\dao\ClientDao;
    use modules\sl\v1\dao\ClientRuleDao;
    use modules\sl\v1\model\cache\RedisCache;
    use modules\sl\v1\model\client\ClientExt;
    use modules\sl\v1\model\client\ClientMember;
    use modules\sl\v1\model\leancloud\ApiRequest;

    class Client extends ModelBase {

        public    $id; //
        public    $str_id; //字符串id
        public    $cdate; //创建时间
        public    $mac_wireless; //无线网卡地址
        public    $mac_wired; //有限网卡
        public    $sn; //电视序列号
        public    $device_name; //设备名称
        public    $device_title; //设备名  美化过的
        public    $device_model; //
        public    $ip; //第一次ip
        public    $oper; //当前用户  sl_oper
        public    $oper_open; //当前操作这的openid
        public    $conversation; //当前会话
        public    $device_type; //设备类型
        public    $mdm_id; //ios用的协议id
        public    $crt_sta; //证书安装状态  true:完成  false:否
        public    $app_ver_code; //终端版本
        public    $ldate; //最后时间
        protected $allAttrKeys = [
            'id',
            'str_id',
            'cdate',
            'mac_wireless',
            'mac_wired',
            'sn',
            'device_name',
            'device_title',
            'device_model',
            'ip',
            'oper',
            'oper_open',
            'conversation',
            'device_type',
            'mdm_id',
            'crt_sta',
            'app_ver_code',
            'ldate'
        ];

        const deviceType_tv_android  = 'tv_android';
        const deviceType_pad_ios     = 'pad_ios';
        const deviceType_pad_android = 'pad_android';
        /**
         * @var ClientDao
         */
        protected $dao      = null;
        private   $__ext    = null;
        private   $__member = null;

        protected $pkAttrsCacheType = RedisCache::clientPk_basicAttrs;


        /**
         * @return ClientDao
         */
        public function getDao() {
            if (is_null($this->dao)) {
                $dao = ClientDao::model()->findByPk($this->id);
                if ($dao === false)
                    Sys::app()->interruption()->setMsg('server_error')->setDebugData($this->id)->outError();
                $this->dao = $dao;
            }
            return $this->dao;
        }


        /**
         * @param $cid  client open id
         * @return Client
         */
        public static function getByOpenId($cid) {
            $pkCache  = RedisCache::model(RedisCache::client_openId_pk, $cid);
            $clientPk = $pkCache->get();
            if (is_null($clientPk)) {
                $model      = new Client();
                $model->dao = ClientDao::findByCid($cid);
                //Sys::app()->logData('recover cache');
                $model->cache = RedisCache::model(RedisCache::clientPk_basicAttrs, $model->dao->id);
                //$cacheData    = $model->dao->getAttributes();
                //$model->cache->set($cacheData);
                $pkCache->set($model->dao->id);
                //Sys::app()->logData([$model->dao->id, $pkCache->key, $cacheData], 'set pkCache');
                $model->reloadDaoAttrs();
                return $model;
            } else {
                return self::getByPk($clientPk);
            }
        }

        /**
         * @param $clientPk  client
         * @return Client
         */
        public static function getByPk($clientPk) {
            $model        = new Client();
            $model->cache = RedisCache::model(RedisCache::clientPk_basicAttrs, $clientPk);
            $cacheData    = $model->cache->get();
            // Sys::app()->logData($cacheData);
            if (is_null($cacheData)) {
                $model->dao = ClientDao::model()->findByPk($clientPk);
                // Sys::app()->logData( $model->dao );
                // $cacheData  = $model->dao->getAttributes();
                //$model->cache->set($cacheData);
                $model->reloadDaoAttrs();
            } else {
                $model->loadByAttrs($cacheData);
            }

            return $model;
        }


        /**
         * 由用户pk 找到下属 终端pk
         * @param $userPk
         * @param $force
         * @return array
         */
        public static function getPksByUserPk($userPk, $force = false) {
            $cache = RedisCache::model(RedisCache::userPk_clientPks, $userPk);
            $cids  = $cache->get();
            if ($force || is_null($cids)) {
                //走过路过不要错过，直接更新名下所有设备的信息
                $cids = [];
                $daos = ClientDao::model()->findAllByAttributes(['oper' => $userPk]);
                foreach ($daos as $dao) {
                    $cids[] = $dao->id;
                    RedisCache::model(RedisCache::clientPk_basicAttrs, $dao->id)->set($dao->getAttributes());
                }
                $cache->set($cids);
            }
            return $cids;
        }

        private static function __getExtAttrsCacheObjectByPk($clientPk) {
            return ClientRuleDao::getCacheObjectByClientPk($clientPk);
        }

        public static function clearCacheExtAttrsByPk($clientPk) {
            self::__getExtAttrsCacheObjectByPk($clientPk)->del();
        }


        public function getOpenInfoAdvance() {
            $oper = intval($this->oper);
            return [
                'clientId'   => $this->str_id,
                'createDate' => $this->cdate,
                'deviceName' => $this->device_title,
                'deviceType' => $this->device_type,
                'convId'     => $this->conversation,
                'oper'       => $oper,
                'operOpenId' => $oper === 0 ? '' : $this->oper_open,
                'mdmId'      => $this->mdm_id,
                'crtSta'     => strval($this->crt_sta) === 'true' ? true : false,
                'appVerCode' => intval($this->app_ver_code),
            ];
        }

        public function getExtAttrsCache() {
            if (is_null($this->id))
                Sys::app()->interruption()->setCode('code_error_')->setMsg('系统错误')->outError();

        }

        public function belongTo(User $user) {
            if (intval($this->oper) === 0) {

                $convId = ApiRequest::createConv($this->str_id, $user->open_id);;
                Sys::app()->logData(['$daoRelat', $this->getDaoAttrs(), $user->getDaoAttrs()]);

                $this->getDao()->oper         = $user->id;
                $this->getDao()->oper_open    = $user->open_id;
                $this->getDao()->conversation = $convId;
                $this->getDao()->save();
                $this->reloadDaoAttrs();
                Client::getPksByUserPk($user->id, true);
                return true;
            }
            return false;
        }

        public function getPkVal() {
            return $this->id;
        }

        /**
         * @return ClientMember
         */
        public function getMember() {
            if (is_null($this->__member))
                $this->__member = new ClientMember($this);
            return $this->__member;
        }

        /**
         * @return ClientExt
         */
        public function getExt() {
            if (is_null($this->__ext))
                $this->__ext = new ClientExt($this);
            return $this->__ext;
        }

        public function isIos() {
            return $this->device_type === self::deviceType_pad_ios ? true : false;
        }

    }