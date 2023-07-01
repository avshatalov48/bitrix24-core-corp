CREATE TABLE b_disk_storage
(
	ID int(11) not null auto_increment,
	NAME varchar(100),
	CODE varchar(32),
	XML_ID varchar(50),

	MODULE_ID varchar(32) not null,
	ENTITY_TYPE varchar(100) not null,
	ENTITY_ID varchar(32) not null,

	ENTITY_MISC_DATA text,
	ROOT_OBJECT_ID int(11),
	USE_INTERNAL_RIGHTS tinyint(1),

	SITE_ID CHAR(2),

	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_PH_1 (MODULE_ID, ENTITY_TYPE, ENTITY_ID),
	KEY IX_DISK_PH_2 (XML_ID)
);

CREATE TABLE b_disk_object
(
	ID int(11) not null auto_increment,
	NAME varchar(255) not null DEFAULT '',
	TYPE int(11) not null,
	CODE varchar(50),
	XML_ID varchar(50),
	STORAGE_ID int(11) not null,
	REAL_OBJECT_ID int(11),
	PARENT_ID int(11),
	CONTENT_PROVIDER varchar(10),

	CREATE_TIME datetime not null,
	UPDATE_TIME datetime,
	SYNC_UPDATE_TIME datetime,
	DELETE_TIME datetime,

	CREATED_BY int(11),
	UPDATED_BY int(11),
	DELETED_BY int(11) DEFAULT 0,

	GLOBAL_CONTENT_VERSION int(11),
	FILE_ID int(11),
	TYPE_FILE int(11),
	SIZE bigint,
	EXTERNAL_HASH varchar(255),
	ETAG VARCHAR(255),
	DELETED_TYPE int(11) DEFAULT 0,
	PREVIEW_ID int(11),
	VIEW_ID int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_O_1 (REAL_OBJECT_ID),
	KEY IX_DISK_O_2 (PARENT_ID, DELETED_TYPE, TYPE),
	KEY IX_DISK_O_3 (STORAGE_ID, CODE),
	KEY IX_DISK_O_4 (STORAGE_ID, DELETED_TYPE, TYPE),
	UNIQUE KEY IX_DISK_O_5 (NAME, PARENT_ID),
	KEY IX_DISK_O_6 (STORAGE_ID, XML_ID),
	KEY IX_DISK_O_7 (UPDATE_TIME),
	KEY IX_DISK_O_8 (SYNC_UPDATE_TIME),
	KEY IX_DISK_O_10 (STORAGE_ID, GLOBAL_CONTENT_VERSION),
	KEY IX_DISK_O_11 (FILE_ID),
	KEY IX_DISK_O_12 (PARENT_ID, STORAGE_ID, UPDATE_TIME)
);

CREATE TABLE b_disk_object_head_index
(
	OBJECT_ID int(11) not null,
	UPDATE_TIME datetime,
	SEARCH_INDEX  mediumtext null,

	PRIMARY KEY (OBJECT_ID),

	FULLTEXT INDEX IX_DISK_HI_1 (SEARCH_INDEX),
	KEY IX_DISK_HI_2 (UPDATE_TIME)
);

CREATE TABLE b_disk_object_extended_index
(
	OBJECT_ID int(11) not null,
	UPDATE_TIME datetime,
	SEARCH_INDEX  mediumtext null,
	STATUS tinyint not null default 2,

	PRIMARY KEY (OBJECT_ID),

	FULLTEXT INDEX IX_DISK_EI_1 (SEARCH_INDEX),
	KEY IX_DISK_EI_2 (STATUS, UPDATE_TIME)
);

CREATE TABLE b_disk_object_lock
(
	ID int(11) not null auto_increment,
	TOKEN varchar(255) NOT NULL,
	OBJECT_ID int(11) not null,
	CREATED_BY int(11) not null,
	CREATE_TIME datetime not null,
	EXPIRY_TIME datetime,
	TYPE int(11) not null,
	IS_EXCLUSIVE tinyint(1),

	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_OL_1 (OBJECT_ID, IS_EXCLUSIVE),
	UNIQUE KEY IX_DISK_OL_2 (TOKEN)
);

CREATE TABLE b_disk_object_ttl
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	CREATE_TIME datetime not null,
	DEATH_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_OTTL_1 (DEATH_TIME, OBJECT_ID)
);

CREATE TABLE b_disk_object_path
(
	ID int(11) not null auto_increment,
	PARENT_ID int(11) not null,
	OBJECT_ID int(11) not null,
	DEPTH_LEVEL int(11),
	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_OP_1 (PARENT_ID, DEPTH_LEVEL, OBJECT_ID),
	UNIQUE KEY IX_DISK_OP_2 (OBJECT_ID, PARENT_ID, DEPTH_LEVEL)
);

CREATE TABLE b_disk_version
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	FILE_ID int(11) not null,
	SIZE bigint,
	NAME varchar(255),
	VIEW_ID int(11),

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	OBJECT_CREATE_TIME datetime,
	OBJECT_CREATED_BY int(11),
	OBJECT_UPDATE_TIME datetime,
	OBJECT_UPDATED_BY int(11),
	GLOBAL_CONTENT_VERSION int(11),

	MISC_DATA text,

	PRIMARY KEY (ID),

	KEY IX_DISK_V_1 (OBJECT_ID),
	KEY IX_DISK_V_2 (CREATE_TIME, OBJECT_ID, FILE_ID)
);

CREATE TABLE b_disk_right
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	TASK_ID int(11) not null,
	ACCESS_CODE varchar(50) not null,
	DOMAIN varchar(50),
	NEGATIVE tinyint(1) not null DEFAULT 0,

	PRIMARY KEY (ID),

	KEY IX_DISK_R_1 (OBJECT_ID, NEGATIVE),
	KEY IX_DISK_R_2 (ACCESS_CODE, TASK_ID)
);

CREATE TABLE b_disk_simple_right
(
	ID bigint unsigned not null auto_increment,
	OBJECT_ID int(11) not null,
	ACCESS_CODE varchar(50) not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_SR_1 (OBJECT_ID),
	KEY IX_DISK_SR_2 (ACCESS_CODE)
);

CREATE TABLE b_disk_right_setup_session
(
	ID int(11) not null auto_increment,
	PARENT_ID int(11),
	OBJECT_ID int(11) not null,
	STATUS tinyint(1) not null DEFAULT 2,
	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_R_SESSION_1 (OBJECT_ID),
	KEY IX_DISK_R_SESSION_2 (STATUS, CREATE_TIME)
);

CREATE TABLE b_disk_tmp_simple_right
(
	OBJECT_ID int(11) not null,
	ACCESS_CODE varchar(50) not null,
	SESSION_ID int(11) not null,

	PRIMARY KEY (SESSION_ID, ACCESS_CODE, OBJECT_ID)
);

CREATE TABLE b_disk_attached_object
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	VERSION_ID int(11),

	IS_EDITABLE tinyint(1) not null DEFAULT 0,
	ALLOW_EDIT tinyint(1) not null DEFAULT 0,
	ALLOW_AUTO_COMMENT tinyint(1) DEFAULT 1,

	MODULE_ID varchar(32) not null,
	ENTITY_TYPE varchar(100) not null,
	ENTITY_ID int(11) not null,

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_AO_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_AO_2 (MODULE_ID, ENTITY_TYPE, ENTITY_ID),
	KEY IX_DISK_AO_3 (ENTITY_ID, ENTITY_TYPE)
);

CREATE TABLE b_disk_external_link
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	VERSION_ID int(11),
	HASH varchar(32) not null,
	PASSWORD varchar(32),
	SALT varchar(32),
	DEATH_TIME datetime,
	DESCRIPTION text,
	DOWNLOAD_COUNT int(11),
	TYPE int(11),
	ACCESS_RIGHT tinyint DEFAULT 0,

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_EL_1 (OBJECT_ID),
	KEY IX_DISK_EL_2 (HASH),
	KEY IX_DISK_EL_3 (CREATED_BY)
);

CREATE TABLE b_disk_sharing
(
	ID int(11) not null auto_increment,
	PARENT_ID int(11),
	CREATED_BY int(11),

	FROM_ENTITY VARCHAR(50) not null,
	TO_ENTITY VARCHAR(50) not null,

	LINK_STORAGE_ID int(11),
	LINK_OBJECT_ID int(11),

	REAL_OBJECT_ID int(11) not null,
	REAL_STORAGE_ID int(11) not null,

	DESCRIPTION text,
	CAN_FORWARD tinyint(1),
	STATUS int(11) not null,
	TYPE int(11) not null,

	TASK_NAME VARCHAR(50),
	IS_EDITABLE tinyint(1) not null DEFAULT 0,

	PRIMARY KEY (ID),

	KEY IX_DISK_S_1 (REAL_STORAGE_ID, REAL_OBJECT_ID),
	KEY IX_DISK_S_2 (FROM_ENTITY),
	KEY IX_DISK_S_3 (TO_ENTITY),
	KEY IX_DISK_S_4 (LINK_STORAGE_ID, LINK_OBJECT_ID),
	KEY IX_DISK_S_5 (TYPE, PARENT_ID),
	KEY IX_DISK_S_6 (REAL_OBJECT_ID, LINK_OBJECT_ID)
);

CREATE TABLE IF NOT EXISTS b_disk_onlyoffice_document_session
(
	ID int not null auto_increment,
	OBJECT_ID int,
	VERSION_ID int,
	USER_ID int not null,
	OWNER_ID int not null,
	IS_EXCLUSIVE tinyint default 0,
	EXTERNAL_HASH varchar(128) not null,
	CREATE_TIME datetime not null,
	TYPE tinyint not null default 0,
	STATUS int default 0,
	CONTEXT text,

	PRIMARY KEY (ID),

	KEY IX_DISK_OODS_1 (EXTERNAL_HASH),
	KEY IX_DISK_OODS_2 (OBJECT_ID, USER_ID)
);

CREATE TABLE b_disk_onlyoffice_document_info
(
	EXTERNAL_HASH varchar(128) not null,
	OBJECT_ID int,
	VERSION_ID int,
	OWNER_ID int not null,
	CREATE_TIME datetime not null,
	UPDATE_TIME datetime not null,
	USERS int not null default 0,
	CONTENT_STATUS int default 0,

	PRIMARY KEY (EXTERNAL_HASH)
);

CREATE TABLE IF NOT EXISTS b_disk_onlyoffice_restriction_log
(
	ID int not null auto_increment,
	USER_ID int not null,
	EXTERNAL_HASH varchar(128) not null,
	STATUS tinyint DEFAULT 0,
	CREATE_TIME datetime,
	UPDATE_TIME datetime,

	PRIMARY KEY (ID),

	KEY IX_DISK_O_RL_UPDATE(UPDATE_TIME),
	KEY IX_DISK_O_RL_USAGE(EXTERNAL_HASH, USER_ID)
);

CREATE TABLE b_disk_edit_session
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11),
	VERSION_ID int(11),
	USER_ID int(11) not null,
	OWNER_ID int(11) not null,
	IS_EXCLUSIVE tinyint(1),
	SERVICE VARCHAR(10) not null,
	SERVICE_FILE_ID VARCHAR(255) not null,
	SERVICE_FILE_LINK text not null,
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_ES_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_ES_2 (USER_ID)
);

CREATE TABLE b_disk_show_session
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11),
	VERSION_ID int(11),
	USER_ID int(11) not null,
	OWNER_ID int(11) not null,
	SERVICE VARCHAR(10) not null,
	SERVICE_FILE_ID VARCHAR(255) not null,
	SERVICE_FILE_LINK text not null,
	ETAG VARCHAR(255),
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_SS_1 (OBJECT_ID, VERSION_ID, USER_ID),
	KEY IX_DISK_SS_2 (CREATE_TIME)
);

CREATE TABLE b_disk_tmp_file
(
	ID int(11) not null auto_increment,
	TOKEN VARCHAR(32) not null,
	FILENAME VARCHAR(255),
	CONTENT_TYPE VARCHAR(255),
	PATH VARCHAR(255),
	BUCKET_ID int(11),
	SIZE bigint,
	RECEIVED_SIZE bigint,
	WIDTH int(11),
	HEIGHT int(11),
	IS_CLOUD tinyint(1),
	CREATED_BY int(11),
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_TF_1 (TOKEN),
	KEY IX_DISK_TF_2 (CREATE_TIME)
);

CREATE TABLE b_disk_deleted_log
(
	ID int(11) not null auto_increment,
	USER_ID int(11) not null,
	STORAGE_ID int(11) not null,
	OBJECT_ID int(11) not null,
	TYPE int(11) not null,
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_DL_1 (STORAGE_ID, CREATE_TIME),
	KEY IX_DISK_DL_2 (OBJECT_ID),
	KEY IX_DISK_DL_3 (CREATE_TIME)
);

CREATE TABLE b_disk_deleted_log_v2
(
    ID bigint unsigned not null auto_increment,
	USER_ID int(11) not null,
	STORAGE_ID int(11) not null,
	OBJECT_ID int(11) not null,
	TYPE int(11) not null,
	CREATE_TIME datetime not null,

    PRIMARY KEY (ID),

	UNIQUE KEY (OBJECT_ID, STORAGE_ID),
	KEY IX_DISK_DL_V2_1 (STORAGE_ID, CREATE_TIME),
	KEY IX_DISK_DL_V2_2 (CREATE_TIME)
);

CREATE TABLE b_disk_cloud_import
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11),
	VERSION_ID int(11),
	TMP_FILE_ID int(11),
	DOWNLOADED_CONTENT_SIZE bigint DEFAULT 0,
	CONTENT_SIZE bigint DEFAULT 0,
	CONTENT_URL text,
	MIME_TYPE VARCHAR(255),
	USER_ID int(11) not null,
	SERVICE VARCHAR(10) not null,
	SERVICE_OBJECT_ID text not null,
	ETAG VARCHAR(255),
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_CI_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_CI_2 (TMP_FILE_ID)
);

CREATE TABLE b_disk_recently_used
(
	ID int(11) not null auto_increment,
	USER_ID int(11) not null,
	OBJECT_ID int(11) not null,
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_RU_1 (USER_ID, OBJECT_ID, CREATE_TIME)
);

CREATE TABLE b_disk_tracked_object
(
	ID int(11) not null auto_increment,
	USER_ID int(11) not null,
	OBJECT_ID int(11) not null,
	REAL_OBJECT_ID int(11) not null,
	ATTACHED_OBJECT_ID int(11),
	CREATE_TIME datetime not null,
	UPDATE_TIME datetime not null,

	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_TO_1 (USER_ID, OBJECT_ID),
	KEY IX_DISK_TO_2 (USER_ID, OBJECT_ID, UPDATE_TIME),
	KEY IX_DISK_TO_3 (ATTACHED_OBJECT_ID, USER_ID),
	KEY IX_DISK_TO_4 (REAL_OBJECT_ID)
);

CREATE TABLE b_disk_volume
(
	ID int(11) not null auto_increment,
	INDICATOR_TYPE varchar(255) not null,
	OWNER_ID int(11) not null default 0,
	CREATE_TIME datetime not null,
	TITLE varchar(255) null default null,
	FILE_SIZE BIGINT not null default 0,
	FILE_COUNT BIGINT not null default 0,
	DISK_SIZE BIGINT not null default 0,
	DISK_COUNT BIGINT not null default 0,
	VERSION_COUNT BIGINT not null default 0,
	PREVIEW_SIZE BIGINT not null default 0,
	PREVIEW_COUNT BIGINT not null default 0,
	ATTACHED_COUNT BIGINT not null default 0,
	LINK_COUNT BIGINT not null default 0,
	SHARING_COUNT BIGINT not null default 0,
	UNNECESSARY_VERSION_SIZE BIGINT not null default 0,
	UNNECESSARY_VERSION_COUNT BIGINT not null default 0,
	PERCENT DECIMAL(18,4) not null default 0,
	STORAGE_ID int(11) null default null,
	MODULE_ID varchar(255) null default null,
	FOLDER_ID int(11) null default null,
	PARENT_ID int(11) null default null,
	USER_ID int(11) null default null,
	GROUP_ID int(11) null default null,
	ENTITY_TYPE varchar(255) null default null,
	ENTITY_ID varchar(255) null default null,
	IBLOCK_ID int(11) null default null,
	TYPE_FILE int(11) null default null,
	COLLECTED tinyint(1) not null default 0,
	AGENT_LOCK tinyint(1) not null default 0,
	DROP_UNNECESSARY_VERSION tinyint(1) not null default 0,
	DROP_TRASHCAN tinyint(1) not null default 0,
	DROP_FOLDER tinyint(1) not null default 0,
	EMPTY_FOLDER tinyint(1) not null default 0,
	DROPPED_FILE_COUNT BIGINT not null default 0,
	DROPPED_VERSION_COUNT BIGINT not null default 0,
	DROPPED_FOLDER_COUNT BIGINT not null default 0,
	DATA TEXT NULL,
	LAST_FILE_ID int(11) null default null,
	FAIL_COUNT int(11) not null default 0,
	LAST_ERROR varchar(255) null default null,

	PRIMARY KEY (ID),

	KEY IX_DISK_VL_1 (OWNER_ID, STORAGE_ID, INDICATOR_TYPE),
	KEY IX_DISK_VL_2 (OWNER_ID, INDICATOR_TYPE),
	KEY IX_DISK_VL_3 (OWNER_ID, AGENT_LOCK)
);

CREATE TABLE b_disk_volume_deleted_log
(
	ID INT(11) NOT NULL AUTO_INCREMENT,
	STORAGE_ID int(11) not null,
	OBJECT_ID int(11) not null,
	OBJECT_PARENT_ID int(11),
	OBJECT_TYPE int(11) not null,
	OBJECT_NAME varchar(255) not null DEFAULT '',
	OBJECT_PATH varchar(255) not null DEFAULT '',
	OBJECT_SIZE BIGINT null default null,
	OBJECT_CREATED_BY int(11) null default null,
	OBJECT_UPDATED_BY int(11) null default null,
	VERSION_ID int(11) null default null,
	VERSION_NAME varchar(255) null default null,
	FILE_ID int(11) null default null,
	DELETED_TIME datetime not null,
	DELETED_BY int(11) DEFAULT 0,
	OPERATION varchar(50) not null DEFAULT '',

	PRIMARY KEY (ID),

	KEY IX_DISK_VL_CL_1 (STORAGE_ID)
);

CREATE TABLE b_disk_attached_view_type
(
	ENTITY_TYPE varchar(100) not null,
	ENTITY_ID int(11) not null,
	VALUE varchar(20) default null,
	primary key (ENTITY_TYPE, ENTITY_ID)
);