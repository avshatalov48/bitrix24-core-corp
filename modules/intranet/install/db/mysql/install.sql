create table if not exists b_intranet_sharepoint
(
	IBLOCK_ID int(11) not null,
	SP_LIST_ID varchar(32) not null,
	SP_URL varchar(255) not null,
	SP_AUTH_USER varchar(50) null default '',
	SP_AUTH_PASS varchar(50) null default '',
	SYNC_DATE datetime,
	SYNC_PERIOD int(11) null default 86400,
	SYNC_ERRORS int(1) null default 0,
	SYNC_LAST_TOKEN varchar(100) null default '',
	SYNC_PAGING varchar(100) null default '',
	HANDLER_MODULE varchar(50) null default '',
	HANDLER_CLASS varchar(100) null default '',
	PRIORITY char(1) null default 'B',
	PRIMARY KEY pk_b_intranet_sharepoint (IBLOCK_ID)
);

create table if not exists b_intranet_sharepoint_field
(
	IBLOCK_ID int(11) not null,
	FIELD_ID varchar(50) not null,
	SP_FIELD varchar(50) not null,
	SP_FIELD_TYPE varchar(50) not null,
	SETTINGS text null,
	PRIMARY KEY pk_b_intranet_sharepoint_field (IBLOCK_ID, FIELD_ID)
);

create table if not exists b_intranet_sharepoint_queue
(
	ID int(11) not null auto_increment,
	IBLOCK_ID int(11) not null,
	SP_METHOD varchar(100) not null,
	SP_METHOD_PARAMS text null,
	CALLBACK text null,
	PRIMARY KEY pk_b_intranet_sharepoint_queue (ID),
	INDEX ix_b_intranet_sharepoint_queue_1 (IBLOCK_ID)
);

create table if not exists b_intranet_sharepoint_log
(
	ID int(11) not null auto_increment,
	IBLOCK_ID int(11) not null,
	ELEMENT_ID int(11) not null,
	VERSION int(5) null default 0,
	PRIMARY KEY pk_b_intranet_sharepoint_log (ID),
	UNIQUE INDEX ui_b_intranet_sharepoint_log (IBLOCK_ID, ELEMENT_ID)
);

create table if not exists b_rating_subordinate (
	ID int(11) NOT NULL auto_increment,
	RATING_ID int(11) NOT NULL,
	ENTITY_ID int(11) NOT NULL,
	VOTES decimal(18,4) NULL default '0.0000',
	PRIMARY KEY	(ID),
	KEY RATING_ID (RATING_ID, ENTITY_ID)
);

create table if not exists b_intranet_ustat_hour (
	`USER_ID` int(11) NOT NULL,
	`HOUR` datetime NOT NULL,
	`TOTAL` smallint(6) unsigned NOT NULL DEFAULT '0',
	`SOCNET` smallint(6) unsigned NOT NULL DEFAULT '0',
	`LIKES` smallint(6) unsigned NOT NULL DEFAULT '0',
	`TASKS` smallint(6) unsigned NOT NULL DEFAULT '0',
	`IM` smallint(6) unsigned NOT NULL DEFAULT '0',
	`DISK` smallint(6) unsigned NOT NULL DEFAULT '0',
	`MOBILE` smallint(6) unsigned NOT NULL DEFAULT '0',
	`CRM` smallint(6) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`USER_ID`,`HOUR`),
	KEY `HOUR` (`HOUR`)
);

create table if not exists b_intranet_ustat_day (
	`USER_ID` int(11) NOT NULL,
	`DAY` date NOT NULL,
	`TOTAL` smallint(6) unsigned NOT NULL DEFAULT '0',
	`SOCNET` smallint(6) unsigned NOT NULL DEFAULT '0',
	`LIKES` smallint(6) unsigned NOT NULL DEFAULT '0',
	`TASKS` smallint(6) unsigned NOT NULL DEFAULT '0',
	`IM` smallint(6) unsigned NOT NULL DEFAULT '0',
	`DISK` smallint(6) unsigned NOT NULL DEFAULT '0',
	`MOBILE` smallint(6) unsigned NOT NULL DEFAULT '0',
	`CRM` smallint(6) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`USER_ID`,`DAY`),
	KEY `DAY` (`DAY`)
);

create table if not exists b_intranet_dstat_hour (
	`DEPT_ID` int(11) NOT NULL,
	`HOUR` datetime NOT NULL,
	`TOTAL` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`SOCNET` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`LIKES` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`TASKS` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`IM` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`DISK` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`MOBILE` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`CRM` mediumint(6) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`DEPT_ID`,`HOUR`),
	KEY `HOUR` (`HOUR`)
);

create table if not exists b_intranet_dstat_day (
	`DEPT_ID` int(11) NOT NULL,
	`DAY` date NOT NULL,
	`ACTIVE_USERS` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`INVOLVEMENT` tinyint(3) unsigned NOT NULL DEFAULT '0',
	`TOTAL` int(11) unsigned NOT NULL DEFAULT '0',
	`SOCNET` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`LIKES` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`TASKS` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`IM` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`DISK` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`MOBILE` mediumint(6) unsigned NOT NULL DEFAULT '0',
	`CRM` mediumint(6) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`DEPT_ID`,`DAY`),
	KEY `DAY` (`DAY`)
);

create table if not exists b_intranet_queue
(
	ENTITY_TYPE varchar(20) NOT NULL,
	ENTITY_ID varchar(10) NOT NULL,
	LAST_ITEM varchar(255) NOT NULL,
	PRIMARY KEY (ENTITY_TYPE, ENTITY_ID)
);

create table if not exists b_intranet_invitation (
	ID int(11) not null auto_increment,
	USER_ID int(11) not null,
	ORIGINATOR_ID int(11) not null,
	INVITATION_TYPE varchar(50) null,
	DATE_CREATE datetime not null,
	INITIALIZED char(1) not null default 'N',
    IS_MASS char(1) not null default 'N',
    IS_DEPARTMENT char(1) not null default 'N',
    IS_INTEGRATOR char(1) not null default 'N',
    IS_REGISTER char(1) not null default 'N',

	PRIMARY KEY(ID),
	INDEX ix_intranet_invitation_created (DATE_CREATE),
	UNIQUE INDEX ix_intranet_invitation_user_originator (USER_ID, ORIGINATOR_ID)
);
create table if not exists b_intranet_theme (
	ID int(11) not null auto_increment,
	THEME_ID varchar(100) not null,
	USER_ID int(11) not null,
	ENTITY_TYPE varchar(50) not null,
	ENTITY_ID int(11) not null,
	CONTEXT varchar(100) not null,

	PRIMARY KEY(ID),
	UNIQUE INDEX ix_intranet_theme_entity_context (ENTITY_TYPE, ENTITY_ID, CONTEXT)
);

create table if not exists b_intranet_custom_section (
	`ID` int unsigned not null auto_increment,
	`CODE` varchar(255) not null,
	`TITLE` varchar(255) not null,
	`MODULE_ID` varchar(50) not null,
	PRIMARY KEY (`ID`),
	UNIQUE ux_intranet_custom_section_table(`CODE`),
	INDEX ix_intranet_custom_section_module_id (`MODULE_ID`)
);

create table if not exists b_intranet_custom_section_page (
	`ID` int unsigned not null auto_increment,
	`CUSTOM_SECTION_ID` int unsigned not null,
	`CODE` varchar(255) not null,
	`TITLE` varchar(255) not null,
	`SORT` int not null,
	`MODULE_ID` varchar(50) not null,
	`SETTINGS` varchar(255) not null,
	PRIMARY KEY (`ID`),
	INDEX ix_intranet_custom_section_page_module_id_settings (`MODULE_ID`, `SETTINGS`)
);

create table if not exists b_intranet_invitation_link
(
	ID int not null AUTO_INCREMENT,
	ENTITY_TYPE varchar(15) not null,
	ENTITY_ID int not null,
	CODE varchar(64) not null,
	CREATED_BY int(11) unsigned null,
	CREATED_AT datetime not null,
	EXPIRED_AT datetime null,
	PRIMARY KEY(ID),
	UNIQUE INDEX ux_b_intranet_invitation_link_entity_type_entity_id (ENTITY_TYPE, ENTITY_ID)
)