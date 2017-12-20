CREATE TABLE `other_url` (
  `oa_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oa_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '页面url',
  `oa_dirpath` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '页面目录',
  `oa_version` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前有效版本号 1开始',
  `oa_contentmd5` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '当前有效版本号内容md5',
  `oa_state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0:启用 1:禁用',
  `oa_runstate` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '最新运行状态 0:待抓取 1:抓取中 2:抓取成功 3:抓取失败',
  `oa_add_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `oa_mod_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `oa_otherid` tinyint(3) unsigned DEFAULT '0' COMMENT '第三方类型',
  PRIMARY KEY (`oa_id`),
  UNIQUE KEY `idx_url` (`oa_url`),
  KEY `idx_add_date` (`oa_add_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='第三方页面url';

CREATE TABLE `other_url_version` (
  `oav_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oav_oaid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '页面url id',
  `oav_version` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '版本号',
  `oav_contentmd5` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '内容md5',
  `oav_themetype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '页面类型',
  `oav_state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0:启用 1:禁用',
  `oav_add_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `oav_mod_date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`oav_id`),
  UNIQUE KEY `uniq_url` (`oav_oaid`,`oav_version`) USING BTREE,
  KEY `idx_add_date` (`oav_add_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='第三方页面获取版本';

