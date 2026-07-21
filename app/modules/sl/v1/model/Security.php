<?php

    namespace modules\sl\v1\model;


    use models\ext\tool\RSA;

    class Security {
        public static function verifyRSASign($str, $sign) {
            $pubKey = file_get_contents(__APP_DIR__ . '/config/file/rsa-public.key');
            return RSA::verify($str, $sign, $pubKey) ? true : false;
        }

        public static function RSASign($str) {
            $priKey = file_get_contents(__APP_DIR__ . '/config/file/rsa-private.key');
            return RSA::sign($str, $priKey);
        }
    }