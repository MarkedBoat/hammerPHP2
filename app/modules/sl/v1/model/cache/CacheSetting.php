<?php

    namespace modules\sl\v1\model\cache;


    class CacheSetting {
        public $key     = '';
        public $ttl     = 0;
        public $valType = '';
        public $parent  = false;

        const valTypeObject = 'object';
        const valTypeArray  = 'array';
        const valTypeString = 'string';
        const valTypeInt    = 'int';
        const valTypeBool   = 'bool';

        /**
         * @param $key
         * @param $ttl
         * @param $valType string
         * @return CacheSetting
         */

        public static function model($key, $ttl, $valType) {
            $model          = new CacheSetting();
            $model->key     = $key;
            $model->ttl     = $ttl;
            $model->valType = $valType;
            return $model;
        }
    }