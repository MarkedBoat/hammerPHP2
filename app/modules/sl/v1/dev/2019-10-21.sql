CREATE TABLE `screen_lock`.`sl_user_member_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `cid` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '分类id  1:领取  2:订单 ',
  `op_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作来源id',
  `op_src` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '操作来源',
  `op_open_id` VARCHAR(32) NOT NULL DEFAULT 0 COMMENT '操作来源id',
  `start_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间  start time',
  `end_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `dur_amount` SMALLINT NOT NULL DEFAULT 0 COMMENT '时效数量',
  `dur_unit` VARCHAR(16) NOT NULL DEFAULT 0 COMMENT '时效单位',
  `is_del` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否作废 1:yes  2:no',
  `is_handle` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否处理  1:yes 2:no',
  `cdate` DATETIME NOT NULL DEFAULT 0,
  `udate` DATETIME NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq` (`oper_id` ASC, `op_src` ASC, `op_id` ASC, `cid` ASC))
  ENGINE = MyISAM
  COMMENT = '用户会员记录表';

ALTER TABLE `screen_lock`.`sl_user_member_log`
  ADD INDEX `index` (`oper_id` ASC, `cid` ASC, `is_del` ASC);

ALTER TABLE `screen_lock`.`sl_user_member_log`
  CHANGE COLUMN `op_src` `op_src` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '操作来源' AFTER `cid`,
  ADD COLUMN `op_sn` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作序号，一次操作的分块，比如讨厌的会员赠送' AFTER `op_id`,
  DROP INDEX `uq` ,
  ADD UNIQUE INDEX `uq` (`op_src` ASC, `op_id` ASC, `op_sn` ASC);

ALTER TABLE `screen_lock`.`sl_member_pkg`
  ADD COLUMN `price_cash_faker` INT NOT NULL DEFAULT 0 COMMENT '伪价格 现金 /分' AFTER `amount_unit`;
update sl_member_pkg set price_cash_faker=price_cash*2;
ALTER TABLE `screen_lock`.`sl_member_pkg`
  ADD COLUMN `tags` VARCHAR(512) NOT NULL DEFAULT '[]' COMMENT '标签' AFTER `price_cash_faker`;
ALTER TABLE `screen_lock`.`sl_member_pkg`
  CHANGE COLUMN `tags` `tags` VARCHAR(512) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL DEFAULT '[]' COMMENT '标签，{border}-{align}-{align} 取值范围 left/top/right/bottom/center  标签要附的边-在边的方向-最终呈现比如 top-right-center   在 上右角（不是右上）居中排列' ;
ALTER TABLE `screen_lock`.`sl_member_pkg`
  ADD COLUMN `sta` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态  1:启用  2:禁用' AFTER `tags`;

ALTER TABLE `screen_lock`.`sl_order_goods`
  ADD COLUMN `uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id 冗余 sl_oper.id' AFTER `fulfill`;
ALTER TABLE `screen_lock`.`sl_order`
  CHANGE COLUMN `pay_sta` `is_pay` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '支付状态  1:支付  2:未支付' ,
  ADD COLUMN `is_refund` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否退款' AFTER `is_pay`;
ALTER TABLE `screen_lock`.`sl_order`
  ADD COLUMN `is_fulfill` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否履行，商品权益是否履行  1:yes 2:no' AFTER `is_refund`;
ALTER TABLE `screen_lock`.`sl_order_goods`
  CHANGE COLUMN `fulfill` `is_fulfill` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '是否履行，商品权益是否履行  1:yes 2:no' ;
ALTER TABLE `screen_lock`.`sl_order_goods`
  ADD COLUMN `cdate` DATETIME NOT NULL DEFAULT 0 COMMENT '创建时间' AFTER `uid`,
  ADD COLUMN `udate` DATETIME NOT NULL DEFAULT 0 COMMENT '修改时间' AFTER `cdate`;
CREATE TABLE `screen_lock`.`sl_cdkey` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cdkey` VARCHAR(32) NOT NULL DEFAULT 0 COMMENT '激活码',
  `cdate` DATETIME NOT NULL DEFAULT 0 COMMENT '创建日期',
  `is_del` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否删除  1:yes 2:no',
  `is_active` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否激活 1:y 2:n',
  `is_fulfill` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否 履行完毕',
  `active_time` DATETIME NOT NULL DEFAULT 0 COMMENT '激活时间',
  `member_cid` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '会员种类',
  `member_amount` SMALLINT NOT NULL DEFAULT 0 COMMENT '会员时长',
  `member_unit` VARCHAR(8) NOT NULL DEFAULT 0 COMMENT '时长单位',
  `oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作用户',
  `benefit_oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '受益人  beneficiary',
  `once_only` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '同类型只能一次，一个用户第二次激活某 limit type能否再次使用',
  `limit_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '类型 限制类型',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `cdkey_UNIQUE` (`cdkey` ASC))
  ENGINE = MyISAM
  COMMENT = '激活码表';

ALTER TABLE `screen_lock`.`sl_cdkey`
  CHANGE COLUMN `active_time` `active_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '激活时间' ;
ALTER TABLE `screen_lock`.`sl_cdkey`
  ADD COLUMN `expires` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '到期时间' AFTER `limit_type`;
ALTER TABLE `screen_lock`.`sl_order`
  ADD COLUMN `benefit_oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '受益人' AFTER `env`,
  ADD COLUMN `client_pk` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '终端pk' AFTER `benefit_oper_id`;
ALTER TABLE `screen_lock`.`sl_order`
  CHANGE COLUMN `uid` `oper_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id sl_oper.id' ,
  CHANGE COLUMN `utk` `oper_tk` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户token sl_oper_log_his.id' ;
ALTER TABLE `screen_lock`.`sl_order_goods`
  CHANGE COLUMN `uid` `oper_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id 冗余 sl_oper.id' ,
  ADD COLUMN `benefit_oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '受益人 用户id' AFTER `oper_id`;
ALTER TABLE `screen_lock`.`sl_cdkey`
  ADD COLUMN `client_pk` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '相应的终端pk' AFTER `expires`;
ALTER TABLE `screen_lock`.`sl_client_rule`
  CHANGE COLUMN `attr_type` `attr_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '属性类型  1 无指定类型  2:基础属性(扩展)  3:记录类型(不能清零)' ;
ALTER TABLE `screen_lock`.`sl_member_pkg`
  ADD COLUMN `apple_id` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '苹果后台产品id' AFTER `sta`;
ALTER TABLE `screen_lock`.`sl_member_pkg`
  ADD COLUMN `tags_pad_ios` VARCHAR(512) NOT NULL DEFAULT '[]' COMMENT '苹果平板标签' AFTER `apple_id`;
ALTER TABLE `screen_lock`.`sl_oper`
  ADD COLUMN `convId` VARCHAR(32) NOT NULL DEFAULT 0 COMMENT '名下的leancloud   convId' AFTER `avatar`,
  ADD COLUMN `had_vip` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '有没有会员' AFTER `convId`;
ALTER TABLE `screen_lock`.`sl_order`
  ADD COLUMN `uq` VARCHAR(32) NULL COMMENT '唯一标识，为坑爹iPay准备的' AFTER `client_pk`,
  ADD UNIQUE INDEX `uq_UNIQUE` (`uq` ASC);
ALTER TABLE `screen_lock`.`sl_oper`
  ADD COLUMN `group_holder` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `group_holder`;

CREATE TABLE `screen_lock`.`sl_group_member` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `holder` INT UNSIGNED NOT NULL DEFAULT 0,
  `member_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '成员类型  1:用户  2:终端',
  `member_pk` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '成员pk',
  `member_open_id` VARCHAR(64) NOT NULL DEFAULT 0 COMMENT '成员 open id',
  `oper_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '终端操作员id',
  `cdate` DATETIME NOT NULL DEFAULT 0 COMMENT '创建时间',
  `req_date` DATETIME NOT NULL DEFAULT 0 COMMENT '申请时间',
  `pass_date` DATETIME NOT NULL DEFAULT 0 COMMENT '通过时间',
  `is_deny` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否被禁止',
  `is_req` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否请求',
  `is_pass` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否通过',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq` (`holder` ASC, `member_type` ASC, `member_pk` ASC),
  INDEX `holder` (`holder` ASC))
  ENGINE = MyISAM
  COMMENT = '组成员列表';
ALTER TABLE `screen_lock`.`sl_client`
  ADD COLUMN `app_ver_code` VARCHAR(16) NOT NULL DEFAULT 0 COMMENT '终端版本' AFTER `crt_sta`,
  ADD COLUMN `ldate` DATETIME NOT NULL DEFAULT 0 COMMENT '最后时间' AFTER `app_ver_code`;


ALTER TABLE `screen_lock`.`sl_group_member`
  CHANGE COLUMN `req_date` `apply_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '申请时间' ,
  CHANGE COLUMN `pass_date` `accept_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '通过时间' ,
  CHANGE COLUMN `is_deny` `is_reject` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '是否被拒绝' ,
  CHANGE COLUMN `is_req` `is_apply` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '是否请求' ,
  CHANGE COLUMN `is_pass` `is_accept` TINYINT(1) NOT NULL DEFAULT '2' COMMENT '是否通过' ,
  ADD COLUMN `reject_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '拒绝时间' AFTER `accept_date`;

ALTER TABLE `screen_lock`.`sl_group_member`
  ADD COLUMN `exit_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '退出时间' AFTER `reject_date`,
  ADD COLUMN `is_exist` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '退出  1:yes 2:no' AFTER `is_accept`;
ALTER TABLE `screen_lock`.`sl_group_member`
  ADD COLUMN `is_member` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '是否为成员 1:yes 2:no' AFTER `is_exist`;

CREATE TABLE `sl_client_member_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_pk` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '终端id',
  `cid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员类型 ',
  `op_src` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作来源',
  `op_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作来源id',
  `op_sn` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作序号，一次操作的分块，比如讨厌的会员赠送',
  `op_open_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '操作来源id',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间  start time',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `dur_amount` smallint(6) NOT NULL DEFAULT '0' COMMENT '时效数量',
  `dur_unit` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '时效单位',
  `is_del` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否作废 1:yes  2:no',
  `is_handle` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否处理  1:yes 2:no',
  `cdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `udate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq` (`op_src`,`op_id`,`op_sn`),
  KEY `index` (`client_pk`,`cid`,`is_del`)
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='终端会员记录表';
CREATE TABLE `sl_client_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tv_android` int(10) unsigned NOT NULL,
  `pad_ios` int(10) unsigned NOT NULL,
  `pad_android` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='终端会员情况';