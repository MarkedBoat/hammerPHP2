<?php

    namespace modules\sl\v1\model\client;


    use hammer\param\Param;
    use hammer\sys\Sys;
    use modules\sl\v1\dao\ClientRuleDao;
    use modules\sl\v1\model\cache\RedisCache;
    use modules\sl\v1\model\Client;
    use modules\sl\v1\model\ExtBase;

    /**
     * Class ClientExt
     * @package modules\sl\v1\model\client
     * @property $_memberExpires
     * @property $_trialNotYet
     * @property $_trialExpires
     * @property $_memberExpiresTrial
     * @property $_bindTime1st
     */
    class ClientExt extends ExtBase {

        public $grand              = [];//
        public $free               = [];
        public $minsMax            = 0;//每天可看时间
        public $acceptDelay        = false;//接受延迟解锁
        public $delayTo            = '';//解锁时间 至
        public $gestureCode        = '0231';// 解锁密码/手势密码
        public $kidAgeTag          = '';//小孩年龄
        public $unlockCode         = '';//解锁密码
        public $kidAgeAsRule       = true;
        public $trialNotYet        = '';//还未试用的vip权限
        public $trialExpires       = 0;//试用期限至,会被业务情况
        public $memberExpires      = 0;//会员到期时间
        public $memberExpiresTrial = 0;//试用期限至,保留性质的
        public $ruleEnable         = true;
        public $bindTime1st        = 0;//第一次绑定操作的时间
        public $loopModeEnble      = false;//护眼模式/循环锁 是否启用
        public $loopModeLockMins   = 30;//循环锁模式，每次锁定时间
        public $loopModeUnlockMins = 2;//循环锁模式,每次解锁时间


        const attrUndefine = 1;
        const attrExt      = 2;
        const attrRecord   = 3;//需要保留记录

        protected $extTableName = '';
        protected $indexField   = 'cid';
        protected $pkField      = '';

        /**
         * @var Client|null
         */
        private $__client = null;
        /**
         * @var RedisCache|null
         */
        private $__cache = null;

        public static $rules = [];

        public function __construct(Client $client, $load = true) {
            $this->trialNotYet  = Sys::app()->params['trial_member_dur'];
            $this->extTableName = ClientRuleDao::tableName;
            $this->__client     = $client;
            $this->dao          = $client;
            $this->__cache      = RedisCache::model(RedisCache::clientPk_extAttrs, $client->id);
            if ($load)
                $this->get();
        }

        /**
         * @return RedisCache|null
         */
        public function getCache() {
            return $this->__cache;
        }

        public function getAttrsRule() {
            if (count(self::$rules) === 0)
                self::$rules = [
                    'grand'              => ['type' => 'array', 'max' => 100, 'default' => [],],
                    'free'               => ['type' => 'array', 'max' => 100, 'default' => [],],
                    //每天可看时间
                    'minsMax'            => ['type' => 'int', 'max' => 1, 'default' => 0,],
                    //接受延迟解锁
                    'acceptDelay'        => ['type' => 'bool', 'max' => 1, 'default' => false,],
                    //解锁时间 至
                    'delayTo'            => ['type' => 'string', 'max' => 1, 'default' => '',],
                    // 解锁密码/手势密码
                    'gestureCode'        => ['type' => 'string', 'max' => 1, 'default' => '0231',],
                    //小孩年龄
                    'kidAgeTag'          => ['type' => 'string', 'max' => 1, 'default' => '',],
                    //解锁密码
                    'unlockCode'         => ['type' => 'string', 'max' => 1, 'default' => '',],
                    //孩子年龄标签作为 规则
                    'kidAgeAsRule'       => ['type' => 'bool', 'max' => 1, 'default' => true,],
                    //可领取的试用期限
                    'trialNotYet'        => [
                        'type'    => 'string',
                        'max'     => 1,
                        'attr'    => self::attrRecord,
                        'default' => Sys::app()->params['trial_member_dur'],
                    ],
                    //试用期限至,原
                    'trialExpires'       => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 0,],
                    //会员期限至
                    'memberExpires'      => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 0,],
                    //规则生效
                    'ruleEnable'         => [
                        'type'    => 'bool',
                        'max'     => 1,
                        'attr'    => self::attrRecord,
                        'default' => true,
                    ],
                    //试用会员到期时间
                    'memberExpiresTrial' => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 0,],
                    //第一次绑定操作的时间
                    'bindTime1st'        => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 0,],
                    //护眼模式/循环锁 是否启用
                    'loopModeEnble'      => [
                        'type'    => 'bool',
                        'max'     => 1,
                        'attr'    => self::attrRecord,
                        'default' => false,
                    ],
                    //循环锁模式，每次锁定时间
                    'loopModeLockMins'   => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 30,],
                    //循环锁模式,每次解锁时间
                    'loopModeUnlockMins' => ['type' => 'int', 'max' => 1, 'attr' => self::attrRecord, 'default' => 2,],
                ];
            return self::$rules;
        }

        public function get2() {
            $cacheData = $this->__cache->get();
            if (is_null($cacheData)) {
                $tn        = ClientRuleDao::tableName;
                $sql       = "select id,attr,sn,val,sta from {$tn} where cid={$this->__client->id}";
                $table     = Sys::app()->db('sl_master')->setText($sql)->queryAll();
                $cacheData = [];
                $rule      = self::getAttrsRule();
                foreach ($rule as $key => $opt)
                    $cacheData[$key] = isset($this->$key) ? $this->$key : null;
                foreach ($table as $row) {
                    // Sys::app()->logData([$row['sta'] === '2', in_array($row['attr'], [ClientRuleDao::attrKidAgeTag])]);
                    if ($row['sta'] === '2' && !in_array($row['attr'], [ClientRuleDao::attrKidAgeTag]))
                        continue;
                    $val = $row['val'];
                    switch ($rule[$row['attr']]['type']) {
                        case RedisCache::valTypeArray:
                            $val = json_decode($val, true);
                            if (!is_array($val))
                                $val = null;
                            break;
                        case RedisCache::valTypeObject:
                            $val = json_decode($val);
                            if (!is_array($val))
                                $val = null;
                            break;
                        case RedisCache::valTypeInt:
                            if ($val === false)
                                $val = null; else $val = intval($val);
                            break;
                        case RedisCache::valTypeBool:
                            $val = in_array($val, [1, true, '1', 'true'], true) ? true : false;
                            break;
                        default:
                            if ($val === false)
                                $val = null;
                            break;
                    }

                    if ($rule[$row['attr']]['max'] === 1) {
                        $cacheData[$row['attr']] = $val;
                    } else {
                        $cacheData[$row['attr']][intval($row['sn'])] = $val;
                    }
                }
                $this->__cache->set($cacheData);
            } else {
                Sys::app()->logData('cache');
            }
            foreach ($cacheData as $attr => $val) {

                if (isset($this->$attr)) {
                    $this->$attr = $val;
                }

            }
            return $this;
        }


        public function setAttrs2($kvs) {
            $rules = self::getAttrsRule();
            $sqls  = [];
            $bind  = [
                ':cid'   => $this->__client->id,
                ':ndate' => date('Y-m-d H:i:s', time()),
            ];
            $tn    = ClientRuleDao::tableName;
            foreach ($kvs as $i => $kv) {
                $attr = $kv['attr'];
                if (!isset($rules[$attr]))
                    Sys::app()->interruption()->setMsg($attr . ':key没有配置')->outError();
                $val = $kv['val'];
                $sn  = isset($kv['sn']) ? intval($kv['sn']) : 0;
                if (($sn + 1) > $rules[$attr]['max'])
                    Sys::app()->interruption()->setMsg('不符合配置')->outError();
                $bind[":val_$i"] = Param::getInputVal($rules[$attr]['type'], $val);
                $sqls[]          = "insert ignore into {$tn} set cid=:cid,attr='{$attr}',sn={$sn},val=:val_{$i},cdate=:ndate on duplicate key update val=:val_{$i},sta=1,udate=:ndate";
            }
            Sys::app()->db('sl_master')->setText(join(';', $sqls))->bindArray($bind)->execute();
            $this->getCache()->del();
            $this->get();
        }

        public function load() {

        }

        public function getOpenInfo() {

            return [
                'grand'         => $this->grand,//
                'free'          => $this->free,
                'totalAmount'   => $this->minsMax,//每天可看时间
                'acceptDelay'   => $this->acceptDelay,//接受延迟解锁
                'delayTo'       => $this->delayTo,//解锁时间 至
                'gestureCode'   => $this->gestureCode,// 解锁密码/手势密码
                'kidAgeTag'     => $this->kidAgeTag,//小孩年龄
                'unlockCode'    => $this->unlockCode,//解锁密码
                'kidAgeAsRule'  => $this->kidAgeAsRule,
                'ruleEnable'    => $this->ruleEnable,
                'memberExpires' => $this->memberExpires,
            ];
        }


        public function clearData() {
            $clientPk  = intval($this->__client->id);
            $tn        = ClientRuleDao::tableName;
            $table     = Sys::app()->db('sl_master')->setText("select id,attr_type from {$tn} where cid={$clientPk}")->queryAll();
            $rules     = self::getAttrsRule();
            $sqlUpdate = [];
            foreach ($table as $row) {
                if (isset($rules[$row['attr']]) && $rules[$row['attr']]['attr'] !== self::attrRecord)
                    $sqlUpdate[] = "update {$tn} set sta=2,udate=:ndate where id={$row['id']}";
            }
            if (count($sqlUpdate))
                Sys::app()->db('sl_master')->setText(join(';', $sqlUpdate))->bindArray([':ndate' => date('Y-m-d H:i:s', time())])->execute();
            $this->getCache()->del();
        }
    }