ALTER TABLE `screen_lock`.`sl_order_goods`
  ADD COLUMN `benefit_client_pk` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '受益的客户端' AFTER `benefit_oper_id`;
ALTER TABLE `screen_lock`.`sl_cdkey`
  ADD COLUMN `benefit_client_pk` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '受益的终端' AFTER `client_pk`;


CREATE TABLE `sl_oper_op_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_pk_op` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人 可能为空',
  `client_pk` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户端',
  `user_pk` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组id',
  `op_action` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '操作 ',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '操作时间',
  `lc_members_input` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `lc_members` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT 'leancloud 成员列表',
  `lc_conv_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT 'leancloud  会话id',
  `lc_action` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT 'leancloud 操作',
  `lc_error` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '错误信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备、成员操作表';