<?php

    namespace modules\sl\v1\model\cache;

    use hammer\sys\Sys;
    use modules\sl\v1\dao\ClientDao;
    use modules\sl\v1\dao\OperDao;

    class RedisKey {
        /**
         * @param OperDao $oper
         * @return CacheSetting
         */
        public static function getWxappletFormIds(OperDao $oper) {
            $operIdInt = intval($oper->id);
            $key       = 'sl_wx_fids_' . $operIdInt;//sl_wxapplet_form_id
            return CacheSetting::model($key, 6000);
        }

        /**
         * @param ClientDao $client
         * @return CacheSetting|bool
         */
        public static function getWxappletFormIdsByClient(ClientDao $client) {
            $operIdInt = intval($client->oper);
            if ($operIdInt === 0)
                return false;
            $key = 'sl_wx_fids_' . $operIdInt;//sl_wxapplet_form_id
            return CacheSetting::model($key, 6000);
        }

        public static function getClientOnlineKey(ClientDao $clientDao, $day) {
            return CacheSetting::model('sl_c_e_ol_' . $clientDao->id . '_' . $day, 86400);//client_event_online_{}_{}
        }
    }