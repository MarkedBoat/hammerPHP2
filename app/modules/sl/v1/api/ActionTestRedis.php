<?php

    namespace modules\sl\v1\api;

    use hammer\web\ActionBase;
    use hammer\sys\Sys;
    use modules\sl\v1\dao\OperDao;
    use modules\sl\v1\model\cache\RedisCache;
    use modules\sl\v1\model\User;

    set_time_limit(60);

    class ActionTestRedis extends ActionBase {
        public static function getClassName() {
            return __CLASS__;
        }

        public function __construct($param = []) {
            parent::init($param);
        }

        public function run() {
            $action = $this->inputBox->getNotEmptyString('action');
            $data   = [];
            switch ($action) {
                case 'createSets':
                    $data = $this->createSets();
                    break;
                case 'clearSets':
                    $data = $this->clearSets();
                    break;
                case 'createSets1':
                    $data = $this->createSets1();
                    break;
                case 'returnTrue':
                    $data = $this->returnTrue();
                    break;
                case 'clearMap':
                    $data = $this->clearMap();
                    break;
                case 'clearMap2':
                    $data = $this->clearMap2();
                    break;
                case 'clearMap3':
                    $data = $this->clearMap3();
                    break;
            }
            return $data;
        }

        public function returnTrue() {
            return true;
        }

        public function clearSets() {
            $tn    = OperDao::tableName;
            $table = Sys::app()->db('sl_slave')->setText("select id from {$tn} ;")->queryAll();
            $redis = Sys::app()->redis('sl');
            $data  = [];
            foreach ($table as $row) {
                $data[$row['id']] = $redis->del('sl_wx_fids_' . $row['id']);
            }
            return $data;

        }

        public function createSets() {
            //return ['cost' => 0, 'list' => []];
            $mct0  = microtime(true);
            $costs = [];
            $cost  = 0;
            for ($i = 0; $i < 100; $i++) {
                $num    = rand(0, 1000000);
                $values = ['sl_test_set_' . $num];
                Sys::app()->redis('sl')->sAdd('sl_test_sets', $num);
                for ($j = 0; $j < 10; $j++)
                    $values[] = rand(1000000, 10000000);
                call_user_func_array([Sys::app()->redis('sl'), 'sAdd'], $values);

                $mct1    = microtime(true);
                $cost    = $mct1 - $mct0;
                $costs[] = $mct1;
                if ($cost > 45)
                    break;
            }
            return ['cost' => $cost, 'list' => $costs];
        }

        public function createSets1() {
            //return ['cost' => 0, 'list' => []];
            $mct0   = microtime(true);
            $userPk = rand(1, 2425);
            $attr   = User::getAttrsCacheByUserPk($userPk);//纯模仿实际不能使用
            $key    = RedisCache::model(RedisCache::userPk_userAttrs, intval($attr['id']))->key;//纯模仿 不能投入使用
            $key    = 'sl_test_set_' . $userPk;
            $values = [$key];
            for ($j = 0; $j < 3; $j++) {
                $formId = md5(rand(10, 10000));
                if (is_string($formId) && !strstr($formId, ' '))
                    $values[] = $formId;
            }
            if (count($values) > 1)
                call_user_func_array([Sys::app()->redis('sl'), 'sAdd'], $values);
            $cost = microtime(true) - $mct0;
            return ['cost' => $cost];
        }

        public function clearMap() {
            Sys::app()->setDebug(true);
            $maps = ['sl_c_info_map', 'sl_c_rule_map', 'sl_oper_set_map', 'sl_user_attr_map', 'sl_client_attr_map'];
            foreach ($maps as $key) {
                $keys = Sys::app()->redis('slx')->hKeys($key);
                array_unshift($keys, $key);
                $r = call_user_func_array([Sys::app()->redis('slx'), 'hDel'], $keys);
                Sys::app()->logData($r, $key);
            }
            for ($i = 2; $i < 17; $i++) {
                $key  = 'sl_c_' . $i;
                $keys = Sys::app()->redis('slx')->hKeys($key);
                array_unshift($keys, $key);
                $r = call_user_func_array([Sys::app()->redis('slx'), 'hDel'], $keys);
                Sys::app()->logData($r, $key);

            }
        }

        public function clearMap2() {
            Sys::app()->setDebug(true);
            $redis = Sys::app()->redis('sl');
            $redis->select(10);
            $maps = ['sl_c_info_map', 'sl_c_rule_map', 'sl_oper_set_map', 'sl_user_attr_map', 'sl_client_attr_map'];
            foreach ($maps as $key) {
                $keys = $redis->hKeys($key);
                array_unshift($keys, $key);
                $r = call_user_func_array([$redis, 'hDel'], $keys);
                Sys::app()->logData($r, $key);
            }
            for ($i = 2; $i < 17; $i++) {
                $key  = 'sl_c_' . $i;
                $keys = $redis->hKeys($key);
                array_unshift($keys, $key);
                $r = call_user_func_array([$redis, 'hDel'], $keys);
                Sys::app()->logData($r, $key);

            }
        }

        public function clearMap3() {
            $map   = $this->inputBox->getNotEmptyString('map');
            $redis = Sys::app()->redis('sl');
            $redis->select(10);
            $keys = $redis->hKeys($map);
            array_unshift($keys, $map);
            $r = call_user_func_array([$redis, 'hDel'], $keys);
            Sys::app()->logData($r, $map);

        }

    }