<?php

    namespace modules\sl\v1\dao\client;

    use hammer\db\DbModel;

    /**
     * Class UserMemberDao
     * @property int id
     * @property int tv_android
     * @property int pad_ios
     * @property int pad_android
     */
    class ClientMemberDao extends DbModel {
        const tableName = 'sl_client_member';

        public function getTableName() {
            return self::tableName;
        }
    }