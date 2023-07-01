CREATE TABLE IF NOT EXISTS `b_rpa_type` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `TITLE` varchar(255) NOT NULL,
  `TABLE_NAME` varchar(64) NOT NULL,
  `IMAGE` varchar(255) NULL,
  `CREATED_BY` int(11) unsigned NOT NULL,
  `SETTINGS` TEXT NULL,
  PRIMARY KEY (ID),
  UNIQUE ux_rpa_type_table(TABLE_NAME)
);

CREATE TABLE IF NOT EXISTS `b_rpa_stage` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `CODE` varchar(50) NULL,
  `COLOR` char(6) NULL,
  `SORT` INT(10) NOT NULL DEFAULT 500,
  `SEMANTIC` varchar(50) NULL,
  `TYPE_ID` int(11) NOT NULL,
  PRIMARY KEY (ID),
  INDEX ux_rpa_stage_type(TYPE_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_field` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TYPE_ID` int(11) NOT NULL,
  `STAGE_ID` int(11) NOT NULL,
  `FIELD` varchar(255) NOT NULL,
  `VISIBILITY` varchar(255) NOT NULL,
  PRIMARY KEY (ID),
  INDEX ix_rpa_field_type_stage(TYPE_ID, STAGE_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_permission` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ENTITY` varchar(50) NOT NULL,
  `ENTITY_ID` int(11) NOT NULL,
  `ACCESS_CODE` varchar(100) NOT NULL,
  `ACTION` varchar(50) NOT NULL,
  `PERMISSION` char(1) NOT NULL,
  PRIMARY KEY (ID),
  INDEX ix_rpa_permission_entities(ENTITY, ENTITY_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_stage_to_stage` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `STAGE_ID` int(11) NOT NULL,
  `STAGE_TO_ID` int(11) NOT NULL,
  PRIMARY KEY (ID),
  INDEX ix_rpa_stage_to_stage_stage_id(STAGE_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_item_history` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ITEM_ID` int(11) NOT NULL,
  `TYPE_ID` int(11) NOT NULL,
  `CREATED_TIME` datetime NOT NULL,
  `STAGE_ID` int(11) NOT NULL,
  `NEW_STAGE_ID` int(11) NULL,
  `USER_ID` int(11) NOT NULL,
  `ACTION` varchar(255) null,
  `SCOPE` varchar(255) NOT NULL DEFAULT 'manual',
  `TASK_ID` int(11) null,
  PRIMARY KEY (ID),
  INDEX ix_rpa_history_type_item(TYPE_ID, ITEM_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_item_history_fields` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ITEM_HISTORY_ID` int(11) NOT NULL,
  `FIELD_NAME` varchar(255) NOT NULL,
  PRIMARY KEY (ID),
  INDEX ix_rpa_history_fields_item(ITEM_HISTORY_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_item_sort` (
  `ID` int(10) unsigned not null AUTO_INCREMENT,
  `USER_ID` int(10) unsigned not null,
  `TYPE_ID` int(10) unsigned not null,
  `ITEM_ID` int(10) unsigned not null,
  `SORT` int(10) not null,
  PRIMARY KEY (ID),
  INDEX ix_rpa_item_sort_ids(USER_ID, TYPE_ID, ITEM_ID)
);

CREATE TABLE IF NOT EXISTS `b_rpa_timeline` (
  `ID` int(10) unsigned not null AUTO_INCREMENT,
  `TYPE_ID` int(10) unsigned not null,
  `ITEM_ID` int(10) unsigned not null,
  `CREATED_TIME` datetime NOT NULL,
  `USER_ID` int(10) unsigned null,
  `TITLE` varchar(255) null,
  `DESCRIPTION` text null,
  `ACTION` varchar(255) null,
  `IS_FIXED` char(1) NOT NULL default 'N',
  `DATA` text null,
  PRIMARY KEY (ID),
  INDEX ix_rpa_timeline_type_item(TYPE_ID, ITEM_ID)
);