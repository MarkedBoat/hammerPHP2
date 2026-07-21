<?php

    namespace modules\sl\v1\model;

    use hammer\sys\Sys;
    use modules\sl\v1\dao\ClientDao;
    use modules\sl\v1\dao\OperDao;
    use modules\sl\v1\dao\OperLogHisDao;
    use modules\sl\v1\dao\OperSettingDao;
    use modules\sl\v1\model\cache\RedisCache;
    use modules\sl\v1\model\user\UserExt;
    use modules\sl\v1\model\user\UserGroup;
    use modules\sl\v1\model\user\UserMember;


    class User extends ModelBase {


        public    $id; //
        public    $open_id; //用户openid
        public    $pad_client_id; //ipad终端id
        public    $open_src; //用户来源
        public    $cdate; //创建时间
        public    $ip; //首次Ip
        public    $nickname; //用户昵称
        public    $avatar; //头像
        public    $convId      = ''; //名下的leancloud   convId
        public    $had_vip; //有没有会员
        public    $group_holder; //群主   oper.id
        protected $allAttrKeys = [
            'id',
            'open_id',
            'pad_client_id',
            'open_src',
            'cdate',
            'ip',
            'nickname',
            'avatar',
            'convId',
            'had_vip',
            'group_holder'
        ];

        const srcWxapplet = 'wxapplet';
        const srcPadIos   = 'pad_ios';

        private $__tokenId = '';
        /**
         * @var OperDao
         */
        protected $dao              = null;
        protected $pkAttrsCacheType = RedisCache::userPk_userAttrs;
        /**
         * @var null|UserMember
         */
        private $__member = null;
        /**
         * @var null|UserExt
         */
        private $__ext = null;

        /**
         * @param UserGroup|null
         */
        private $__group = null;

        public function __construct1(OperDao $dao) {
            $this->dao = $dao;
        }

        /**
         * @return OperDao
         */
        public function getDao() {
            if (is_null($this->dao)) {
                $dao = OperDao::model()->findByPk($this->id);
                if ($dao === false)
                    Sys::app()->interruption()->setMsg('server_error')->outError();
                $this->dao = $dao;
            }
            return $this->dao;
        }

        /**
         * @param $userToken
         * @return User
         */
        public static function getUserByToken($userToken) {
            $ar = explode('#', $userToken);
            if (count($ar) < 3)
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_error')->outError();
            $tokenId = intval($ar[0]);
            $expires = intval($ar[1]);
            $sign    = $ar[2];
            if ($tokenId < 0)
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_error')->outError();
            if ($expires < time())
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_expired')->outError();
            if (Security::verifyRSASign($tokenId . '#' . $expires, $sign) === false)
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_sign')->outError();

            $log = OperLogHisDao::model()->findByPk($tokenId);
            if ($log === false)
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_not_exist')->outError();
            if (intval($log->expires) !== $expires || intval($log->sta) !== OperLogHisDao::staYes)
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_token_expired')->outError();
            /*
            $oper = OperDao::model()->findByPk($log->oper);
            if (is_null($oper))
                Sys::app()->interruption()->setMsg('请重新登录')->setCode('user_error_not_exist')->outError();*/
            $user = self::getByPk($log->oper);
            // $user->loadByAttrs(self::getAttrsCacheByUserPk($log->oper));
            $user->__tokenId = $tokenId;
            return $user;
        }


        /**
         * @param $openId
         * @return User
         */
        public static function getByOpenId($openId, $openSrc = '') {
            $pkCache  = RedisCache::model(RedisCache::user_openId_pk, $openId);
            $clientPk = $pkCache->get();
            if (is_null($clientPk)) {
                $model = new User();
                if (!$openSrc)
                    $openSrc = 'wxapplet';
                $dao = OperDao::model()->findByAttributes(['open_id' => $openId, 'open_src' => $openSrc]);
                if ($dao === false)
                    Sys::app()->interruption()->setMsg('找不到用户')->setCode('oper_not_exist')->outError();
                Sys::app()->logData('recover cache');
                $model->dao   = $dao;
                $model->cache = RedisCache::model(RedisCache::userPk_userAttrs, $model->dao->id);
                //  $cacheData    = $model->dao->getAttributes();
                // $model->cache->set($cacheData);
                $pkCache->set($model->dao->id);
                $model->reloadDaoAttrs();
                return $model;
            } else {
                return self::getByPk($clientPk);
            }
        }


        /**
         * @param $userPk  int
         * @return User
         */
        public static function getByPk($userPk) {
            $model = new User();
            if (intval($userPk) === 0)
                return $model;
            $model->cache = RedisCache::model(RedisCache::userPk_userAttrs, $userPk);
            $cacheData    = $model->cache->get();
            // Sys::app()->logData([$userPk, $model->cache, $cacheData], 'user getByPk');
            if (is_null($cacheData)) {
                //   Sys::app()->logData('recover cache');
                $model->dao = OperDao::model()->findByPk($userPk);
                //$cacheData  = $model->dao->getAttributes();
                //$model->cache->set($cacheData);
               // Sys::app()->logData([$model->dao,$model->dao->getAttributes()]);
                $model->reloadDaoAttrs();
            } else {
                $model->loadByAttrs($cacheData);
            }
            return $model;
        }


        public function getOpenInfo($level = 0) {
            $data = [
                'id'       => $this->id,
                'openId'   => $this->open_id,
                'openSrc'  => $this->open_src,
                'avatar'   => $this->avatar === '0' ? '' : $this->avatar,
                'nickname' => $this->nickname === '0' ? '' : $this->nickname,
                'member'   => null,
            ];
            if ($level > 0) {
                $data['member'] = (new UserMember($this))->getOpenInfo();
            }
            if ($level > 0) {
                //  $data['member'] = (new UserMember($this))->getOpenInfo();
            }
            return $data;
        }

        public function getToken() {
            return $this->__tokenId;
        }

        public function annex(User $user) {
            $isIos    = $user->open_src === User::srcPadIos ? true : false;
            $clientTn = ClientDao::tableName;
            Sys::app()->db('sl_master')->setText("update ignore {$clientTn} set oper=:operNew where oper=:operOld")->bindArray([
                ':operNew' => $this->id,
                ':operOld' => $user->id
            ])->execute();

            if ($isIos === false) {
                $settingTn = OperSettingDao::tableName;
                Sys::app()->db('sl_master')->setText("update ignore {$settingTn} set oper_id=:operNew where oper_id=:operOld")->bindArray([
                    ':operNew' => $this->id,
                    ':operOld' => $user->id
                ])->execute();
            }
            $thisMember  = new UserMember($this);
            $userMember  = new UserMember($user);
            $allAttrKeys = ['tv_android', 'pad_ios', 'pad_android'];
            $isChange    = false;
            foreach ($allAttrKeys as $key) {
                if (intval($thisMember->$key) < intval($userMember->$key)) {
                    $thisMember->getDao()->$key = $userMember->$key;
                    $isChange                   = true;
                }
            }
            if ($isChange)
                $thisMember->getDao()->save();

        }

        public function getPkVal() {
            return $this->id;
        }

        /**
         * @return UserMember
         */
        public function getMember() {
            if (is_null($this->__member))
                $this->__member = new UserMember($this);
            return $this->__member;
        }

        /**
         * @return UserExt
         */
        public function getExt() {
            if (is_null($this->__ext))
                $this->__ext = new UserExt($this);
            return $this->__ext;
        }

        public function getGroup() {
            if (is_null($this->__group))
                $this->__group = new UserGroup($this);
            return $this->__group;
        }


        /**
         * @param $userPk
         * @return bool
         */
        public static function getFromId($userPk) {
            $cache     = RedisCache::model(RedisCache::userPk_appletFormIds, $userPk);
            $cacheData = $cache->get();
            Sys::app()->logData([$cacheData, $userPk]);
            $nowTs  = time();
            $len    = count($cacheData);
            $fromId = false;
            for ($i = 0; $i < $len; $i++) {
                list($fromId, $expires) = explode('_', array_shift($cacheData));
                if ($expires > $nowTs) {
                    $cache->set($cacheData);
                    break;
                }
            }
            return $fromId;
        }

    }