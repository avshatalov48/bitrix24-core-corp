CREATE TABLE IF NOT EXISTS b_voximplant_phone
(
	ID int(11) NOT NULL auto_increment,
	USER_ID int(11) NOT NULL,
	PHONE_NUMBER varchar(20) NOT NULL,
	PHONE_MNEMONIC varchar(20) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_PH_1 (USER_ID, PHONE_NUMBER),
	KEY IX_VI_PH_3 (PHONE_NUMBER),
	UNIQUE KEY UX_VI_PH_2 (USER_ID, PHONE_MNEMONIC)
);

CREATE TABLE IF NOT EXISTS b_voximplant_statistic
(
	ID int(11) NOT NULL auto_increment,
	ACCOUNT_ID int(11) NULL,
	APPLICATION_ID int(11) NULL,
	APPLICATION_NAME varchar(80) NULL,
	PORTAL_USER_ID int(11) NULL,
	PORTAL_NUMBER varchar(50) NULL,
	PHONE_NUMBER varchar(20) NOT NULL,
	INCOMING varchar(50) not null default '1',
	SESSION_ID bigint unsigned NULL,
	CALL_ID varchar(255) NOT NULL,
	EXTERNAL_CALL_ID varchar(64) NULL,
	CALL_CATEGORY varchar(20) NULL default 'external',
	CALL_LOG varchar(2000) NULL,
	CALL_DIRECTION varchar(255) NULL,
	CALL_DURATION int(11) NOT NULL default 0,
	CALL_START_DATE datetime not null,
	CALL_STATUS int(11) NULL default 0,
	CALL_FAILED_CODE varchar(255) NULL,
	CALL_FAILED_REASON varchar(255) NULL,
	CALL_RECORD_ID INT(11) NULL,
	CALL_RECORD_URL varchar(2000) NULL,
	CALL_WEBDAV_ID INT(11) NULL,
	CALL_VOTE smallint(1) DEFAULT 0,
	COST double(11, 4) NULL default 0,
	COST_CURRENCY varchar(50) NULL,
	CRM_ENTITY_TYPE varchar(50) NULL,
	CRM_ENTITY_ID int(11) NULL,
	CRM_ACTIVITY_ID int(11) NULL,
	REST_APP_ID int(11) NULL,
	REST_APP_NAME varchar(255) NULL,
	TRANSCRIPT_PENDING char(1) NULL,
	TRANSCRIPT_ID int(11) NULL,
	REDIAL_ATTEMPT int(11) NULL,
	COMMENT text null,
	RECORD_DURATION int(11) NULL,
	PRIMARY KEY (ID),
	KEY IX_VI_ST_2 (CALL_START_DATE),
	KEY IX_VI_ST_3 (CALL_FAILED_CODE),
	KEY IX_VI_ST_4 (CALL_CATEGORY),
	KEY IX_VI_ST_5 (CALL_VOTE),
	KEY IX_VI_ST_6 (CALL_ID),
	KEY IX_VI_ST_7 (CALL_START_DATE, CALL_CATEGORY, CALL_DURATION, COST),
	KEY IX_VI_ST_8 (EXTERNAL_CALL_ID),
	KEY IX_VI_ST_9 (PORTAL_USER_ID, CALL_START_DATE),
	KEY IX_VI_ST_10 (CRM_ACTIVITY_ID),
	KEY IX_VI_ST_11 (CALL_RECORD_ID),
	KEY IX_VI_ST_12 (CALL_WEBDAV_ID),
	KEY IX_VI_ST_13 (CRM_ENTITY_ID, CRM_ENTITY_TYPE),
	KEY IX_VI_ST_14 (SESSION_ID),
    FULLTEXT INDEX IXF_VI_ST_1 (COMMENT)
);

CREATE TABLE IF NOT EXISTS b_voximplant_statistic_index
(
	STATISTIC_ID int(11) NOT NULL auto_increment,
	CONTENT mediumtext,
	PRIMARY KEY (STATISTIC_ID),
    FULLTEXT INDEX IXF_VI_STATS_1 (CONTENT)
);

CREATE TABLE IF NOT EXISTS b_voximplant_statistic_missed
(
    ID int(11) NOT NULL,
    CALL_START_DATE datetime not null,
    PHONE_NUMBER varchar(20) NOT NULL,
    PORTAL_USER_ID int(11) NULL,
    CALLBACK_ID int(11) NULL,
    CALLBACK_CALL_START_DATE datetime NULL,
    PRIMARY KEY(ID),
    KEY IX_VI_ST_1 (PHONE_NUMBER),
    KEY IX_VI_ST_2 (CALLBACK_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_call
(
	ID int(11) NOT NULL auto_increment,
	CONFIG_ID int(11) NULL,
	USER_ID int(11) NULL,
	PORTAL_USER_ID int(11) NULL,
	CALL_ID varchar(255) NOT NULL,
	EXTERNAL_CALL_ID varchar(64) NULL,
	SESSION_ID bigint unsigned NULL,
	INCOMING varchar(50) null,
	CALLER_ID varchar(255) NULL,
	STATUS varchar(50) NULL,
	CRM char(1) not null default 'Y',
	CRM_ACTIVITY_ID int(11) NULL,
	CRM_CALL_LIST int(11) NULL,
	CRM_BINDINGS text NULL,
	ACCESS_URL varchar(255) NULL,
	DATE_CREATE datetime,
	REST_APP_ID int(11) NULL,
	EXTERNAL_LINE_ID int(11) NULL,
	PORTAL_NUMBER varchar(255) NULL,
	STAGE varchar(50) NULL,
	IVR_ACTION_ID int NULL,
	QUEUE_ID int(11) NULL,
	QUEUE_HISTORY text null,
	CALLBACK_PARAMETERS text null,
	COMMENT text null,
	WORKTIME_SKIPPED char(1) not null default 'N',
	SIP_HEADERS text null,
	GATHERED_DIGITS varchar(15) null,
	PARENT_CALL_ID varchar(255),
	LAST_PING datetime,
	EXECUTION_GRAPH text null,
	PRIMARY KEY (ID),
	KEY IX_VI_I_1 (CALL_ID),
	KEY IX_VI_I_2 (DATE_CREATE, STATUS),
	KEY IX_VI_I_3 (SESSION_ID),
	KEY IX_VI_I_4 (PARENT_CALL_ID),
	KEY IX_VI_I_5 (EXTERNAL_CALL_ID),
	KEY IX_VI_I_6 (STATUS,LAST_PING)
);

CREATE TABLE IF NOT EXISTS b_voximplant_call_user
(
	CALL_ID varchar(255) not null,
	USER_ID int not null,
	ROLE varchar(50),
	STATUS varchar(50),
	DEVICE varchar(50),
	INSERTED datetime,
	PRIMARY KEY (CALL_ID, USER_ID),
	INDEX IX_VI_CALL_USER_2(CALL_ID(100), USER_ID, ROLE, STATUS)
);

CREATE TABLE IF NOT EXISTS b_voximplant_call_crm_entity
(
	CALL_ID varchar(255) not null,
	ENTITY_TYPE varchar(50) not null,
	ENTITY_ID int not null,
	IS_PRIMARY char(1) not null default 'N',
	IS_CREATED char(1) not null default 'N',

	PRIMARY KEY(CALL_ID, ENTITY_TYPE, ENTITY_ID),
	INDEX IX_VI_CALL_CRM_1(ENTITY_ID, ENTITY_TYPE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_sip
(
	ID int(11) NOT NULL auto_increment,
	APP_ID varchar(128) NULL,
	CONFIG_ID int(11) NOT NULL,
	TYPE varchar(255) NULL DEFAULT 'office',
	REG_ID int(11) NULL DEFAULT 0,
	SERVER varchar(255) NULL,
	LOGIN varchar(255) NULL,
	PASSWORD varchar(255) NULL,
	INCOMING_SERVER varchar(255) NULL,
	INCOMING_LOGIN varchar(255) NULL,
	INCOMING_PASSWORD varchar(255) NULL,
	AUTH_USER varchar(255) NULL,
	OUTBOUND_PROXY varchar(255) NULL,
	DETECT_LINE_NUMBER char(1) NOT NULL DEFAULT "N",
	LINE_DETECT_HEADER_ORDER varchar(32) NOT NULL DEFAULT "diversion;to",
    REGISTRATION_STATUS_CODE int,
    REGISTRATION_ERROR_MESSAGE text default null,
	PRIMARY KEY (ID),
	KEY IX_VI_SIP_1 (CONFIG_ID),
	KEY IX_VI_SIP_2 (APP_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_external_line
(
	ID int(11) NOT NULL auto_increment,
	TYPE varchar(16) NOT NULL DEFAULT "rest-app",
	NUMBER varchar(50) NOT NULL,
	NORMALIZED_NUMBER varchar(50) NOT NULL,
	NAME varchar(255) NULL,
	REST_APP_ID int(11) NULL,
	SIP_ID int NULL,
	IS_MANUAL char(1) NOT NULL DEFAULT "N",
	CRM_AUTO_CREATE char(1) NOT NULL DEFAULT "Y",
	DATE_CREATE datetime,
	PRIMARY KEY(ID),
	UNIQUE INDEX IX_VI_EXTERNAL_LINE_1 (REST_APP_ID, NUMBER),
	UNIQUE INDEX IX_VI_EXTERNAL_LINE_2 (SIP_ID, NORMALIZED_NUMBER, TYPE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_number
(
	ID int NOT NULL auto_increment,
	NUMBER varchar(50) NOT NULL,
	NAME varchar(255) NULL,
	COUNTRY_CODE varchar(50) NULL,
	VERIFIED char(1) NULL default 'Y',
	DATE_CREATE datetime,
	SUBSCRIPTION_ID int,
	CONFIG_ID int,
	TO_DELETE char(1) null default 'N',
	DATE_DELETE datetime null,

	PRIMARY KEY (ID),
	UNIQUE INDEX IX_VI_NUMBER_1(NUMBER),
	INDEX IX_VI_NUMBER_2(CONFIG_ID),
	INDEX IX_VI_NUMBER_3 (TO_DELETE, DATE_DELETE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_caller_id
(
	ID int NOT NULL auto_increment,
	NUMBER varchar(50) NOT NULL,
	VERIFIED char(1) NULL default 'N',
	DATE_CREATE datetime,
	VERIFIED_UNTIL date,
	CONFIG_ID int,

	PRIMARY KEY (ID),
	UNIQUE INDEX IX_VI_CALLER_ID_1(NUMBER),
	INDEX IX_VI_CALLER_ID_2(CONFIG_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_config
(
	ID int(11) NOT NULL auto_increment,
	PORTAL_MODE varchar(50),
	SEARCH_ID varchar(255) NULL,
	PHONE_NAME varchar(255) NULL,
	PHONE_COUNTRY_CODE varchar(50) NULL,
	PHONE_VERIFIED char(1) null default 'Y',
	CRM char(1) not null default 'Y',
	CRM_RULE varchar(50),
	CRM_CREATE varchar(50) default 'none',
	CRM_CREATE_CALL_TYPE varchar(30) default 'all',
	CRM_SOURCE varchar(50) default 'CALL',
	CRM_FORWARD char(1) not null default 'Y',
	CRM_TRANSFER_CHANGE char(1) null default 'N',
	IVR char(1) not null default 'N',
	QUEUE_ID int(11) null,
	IVR_ID int(11) null,
	DIRECT_CODE char(1) not null default 'Y',
	DIRECT_CODE_RULE varchar(50),
	RECORDING char(1) not null default 'Y',
	RECORDING_NOTICE char(1) null default 'N',
	RECORDING_TIME smallint(1) DEFAULT 0,
	RECORDING_STEREO char(1) DEFAULT 'N',
	VOTE char(1) null default 'N',
	FORWARD_LINE varchar(255) default 'default',
	TIMEMAN char(1) not null default 'N',
	VOICEMAIL char(1) not null default 'Y',
	MELODY_LANG char(2) not null default 'EN',
	MELODY_WELCOME int(11) null,
	MELODY_WELCOME_ENABLE char(1) not null default 'Y',
	MELODY_VOICEMAIL int(11) null,
	MELODY_WAIT int(11) null,
	MELODY_ENQUEUE int(11) null,
	MELODY_HOLD int(11) null,
	MELODY_RECORDING int(11) null,
	MELODY_VOTE int(11) null,
	MELODY_VOTE_END int(11) null,
	WORKTIME_ENABLE char(1) null default 'N',
	WORKTIME_FROM varchar(5) null,
	WORKTIME_TO varchar(5) null,
	WORKTIME_TIMEZONE varchar(50) null,
	WORKTIME_HOLIDAYS varchar(2000) null,
	WORKTIME_DAYOFF varchar(20) null,
	WORKTIME_DAYOFF_RULE varchar(50) default 'voicemail',
	WORKTIME_DAYOFF_NUMBER varchar(20) null,
	WORKTIME_DAYOFF_MELODY int(11) null,
	WAIT_CRM int(11) not null default 30,
	WAIT_DIRECT int(11) not null default 30,
	USE_SIP_TO char(1) null default 'N',
	TRANSCRIBE char(1) null default 'N',
	TRANSCRIBE_LANG char(15) null,
	TRANSCRIBE_PROVIDER varchar(25) null,
	CALLBACK_REDIAL char(1) null default 'N',
	CALLBACK_REDIAL_ATTEMPTS int(11) null,
	CALLBACK_REDIAL_PERIOD int(11) null,
	LINE_PREFIX char(10) null,
	CAN_BE_SELECTED char(1) null default 'N',
	BACKUP_NUMBER varchar(255) null,
	BACKUP_LINE varchar(255) null,
	REDIRECT_WITH_CLIENT_NUMBER char(1) default 'N',
	PRIMARY KEY (ID),
	KEY IX_VI_PC_1 (SEARCH_ID),
	KEY IX_VI_PC_2 (PORTAL_MODE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_line_access
(
	ID int(11) NOT NULL auto_increment,
	CONFIG_ID int,
	LINE_SEARCH_ID varchar(255) NULL,
	ACCESS_CODE varchar(100) NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_queue
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NULL,
	TYPE varchar(50) DEFAULT 'evenly',
	WAIT_TIME smallint(3) DEFAULT 0,
	NO_ANSWER_RULE varchar(50) DEFAULT 'voicemail',
	NEXT_QUEUE_ID int(11) NULL,
	FORWARD_NUMBER varchar(20) NULL,
	PHONE_NUMBER varchar(20) NULL,
	ALLOW_INTERCEPT char(1) NOT NULL default 'N',
	PRIMARY KEY(ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_queue_user
(
	ID int(11) NOT NULL auto_increment,
	QUEUE_ID int(11) NOT NULL,
	USER_ID int(11) NOT NULL,
	STATUS varchar(50) NULL,
	LAST_ACTIVITY_DATE datetime,
	PRIMARY KEY (ID),
	KEY IX_VI_PQ_1 (USER_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_blacklist
(
	ID int(11) NOT NULL auto_increment,
	PHONE_NUMBER varchar(20) NULL,
	NUMBER_STRIPPED varchar(20) NULL,
	NUMBER_E164 varchar(20) NULL,
	INSERTED datetime,
	PRIMARY KEY (ID),
	KEY IX_VI_BL_1 (PHONE_NUMBER),
	INDEX IX_VI_BL_2 (NUMBER_E164),
	INDEX IX_VI_BL_3 (NUMBER_STRIPPED)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role_permission
(
	ID int(11) NOT NULL auto_increment,
	ROLE_ID int(11) NOT NULL,
	ENTITY varchar(50) NOT NULL,
	ACTION varchar(50) NOT NULL,
	PERMISSION char(1) NULL,

	PRIMARY KEY (ID),
	KEY IX_VOX_PERM_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_role_access
(
	ID int(11) NOT NULL auto_increment,
	ROLE_ID int(11) NOT NULL,
	ACCESS_CODE varchar(100) NOT NULL,

	PRIMARY KEY(ID),
	KEY IX_VOX_ACCESS_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr
(
	ID int(11) NOT NULL auto_increment,
	NAME varchar(255) NOT NULL,
	FIRST_ITEM_ID int(11) NULL,

	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr_item
(
	ID int(11) NOT NULL auto_increment,
	CODE varchar(50) NULL,
	IVR_ID int(11) NULL,
	NAME varchar(255) NULL,
	TYPE varchar(10) NOT NULL default 'message',
	URL varchar(2000),
	MESSAGE text,
	FILE_ID int(11) NULL,
	TIMEOUT int(11) NOT NULL default 15,
   	TIMEOUT_ACTION varchar(255) NOT NULL default 'exit',
	TTS_VOICE varchar(50),
	TTS_SPEED varchar(20),
	TTS_VOLUME varchar(20),

	PRIMARY KEY (ID),
	UNIQUE INDEX ux_voximplant_ivr_item (IVR_ID, CODE)
);

CREATE TABLE IF NOT EXISTS b_voximplant_ivr_action
(
	ID int(11) NOT NULL auto_increment,
	ITEM_ID int(11) NOT NULL,
	DIGIT char(1) NULL,
	ACTION varchar(255) NOT NULL,
	PARAMETERS text,
	LEAD_FIELDS text,

	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_transcript
(
	ID int(11) NOT NULL auto_increment,
	SESSION_ID bigint unsigned NULL,
	CALL_ID varchar(255) NULL,
	URL varchar(255) NULL,
	CONTENT text NULL,
	COST double(11, 4) NULL default 0,
	COST_CURRENCY varchar(50) NULL,
	PRIMARY KEY(ID),
	INDEX IX_VOX_TRANS_1(SESSION_ID),
	INDEX IX_VOX_TRANS_2(CALL_ID)
);

CREATE TABLE IF NOT EXISTS b_voximplant_transcript_line
(
	ID int(11) NOT NULL auto_increment,
	TRANSCRIPT_ID int(11) NOT NULL,
	SIDE varchar(10) NOT NULL,
	START_TIME int NOT NULL,
	STOP_TIME int NOT NULL,
	MESSAGE text,
	PRIMARY KEY (ID),
	INDEX IX_VOX_TRANS_LINE_1(TRANSCRIPT_ID),
    FULLTEXT INDEX IXF_VI_TRANSCRIPT_LINE_1 (MESSAGE)
);