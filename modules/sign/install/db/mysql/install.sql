create table if not exists b_sign_blank
(
	ID int(18) not null auto_increment,
	TITLE varchar(255) not null,
	EXTERNAL_ID int(18) default null,
	HOST varchar(255) default null,
	FILE_ID text not null,
	STATUS text null,
	CONVERTED char(1) not null default 'N',
	SCENARIO tinyint unsigned not null default 0,
	CREATED_BY_ID int(18) not null,
	MODIFIED_BY_ID int(18) not null,
	DATE_CREATE timestamp null,
	DATE_MODIFY timestamp not null,
	FOR_TEMPLATE tinyint not null default 0,
	PRIMARY KEY(ID),
	INDEX IX_B_EXTERNAL_HOST (EXTERNAL_ID, HOST)
);

create table if not exists b_sign_document
(
	ID int(18) not null auto_increment,
	TITLE varchar(255) default null,
	HASH char(19) default null,
	SEC_CODE char(20) default null,
	HOST varchar(255) default null,
	BLANK_ID int(18) not null,
	SCENARIO tinyint unsigned null,
	UID varchar(20) null,
	STATUS varchar(10) null,
	ENTITY_TYPE varchar(20) not null,
	ENTITY_ID int(18) not null,
	META text default null,
	PROCESSING_STATUS char(1) not null default 'B',
	PROCESSING_ERROR text default null,
	LANG_ID char(2) default null,
	RESULT_FILE_ID int(18) default null,
	VERSION tinyint unsigned default 1,
	CREATED_BY_ID int(18) not null,
	MODIFIED_BY_ID int(18) not null,
	GROUP_ID bigint unsigned null,
	DATE_CREATE timestamp null,
	DATE_MODIFY timestamp not null,
	DATE_SIGN timestamp not null default 0,
	COMPANY_UID varchar(32) null default null,
	REPRESENTATIVE_ID int(18) null default null,
	PARTIES int not null default 2,
	EXTERNAL_ID varchar(255) null default null,
	SCHEME tinyint unsigned default 0,
	REGION_DOCUMENT_TYPE varchar(255) null default null,
	STOPPED_BY_ID int null default null,
	EXTERNAL_DATE_CREATE datetime null default null,
	PROVIDER_CODE varchar(255) null default null,
	TEMPLATE_ID bigint unsigned null default null,
	CHAT_ID int default null,
	CREATED_FROM_DOCUMENT_ID int null default null,
	INITIATED_BY_TYPE tinyint default 0,
	HCMLINK_COMPANY_ID int(18) unsigned null default null,
	DATE_STATUS_CHANGED datetime null default null,
	PRIMARY KEY(ID),
	INDEX IX_B_ENTITY (ENTITY_TYPE, ENTITY_ID),
	INDEX IX_B_HOST (HOST),
	INDEX IX_B_UID (UID),
	INDEX IX_B_COMPANY (COMPANY_UID),
	INDEX IX_B_BLANK_ID (BLANK_ID),
	INDEX IX_B_GROUP_ID (GROUP_ID),
	INDEX IX_B_CREATED_BY_ID (CREATED_BY_ID),
	UNIQUE UK_SIGN_DOCUMENT_HASH (HASH)
);

create table if not exists b_sign_member
(
	ID bigint unsigned not null auto_increment,
	DOCUMENT_ID bigint unsigned not null,
	CONTACT_ID bigint unsigned not null,
	PART int(2) not null,
	HASH char(32) not null,
	SIGNED char(1) not null default 'N',
	VERIFIED char(1) not null default 'N',
	MUTE char(1) not null default 'N',
	COMMUNICATION_TYPE varchar(20),
	COMMUNICATION_VALUE varchar(100),
	USER_DATA text,
	META text default null,
	SIGNATURE_FILE_ID int(18) default null,
	STAMP_FILE_ID int(18) default null,
	CREATED_BY_ID int(18) not null,
	MODIFIED_BY_ID int(18) not null,
	DATE_CREATE timestamp null,
	DATE_MODIFY timestamp not null,
	DATE_SIGN timestamp not null default 0,
	DATE_DOC_DOWNLOAD timestamp not null default 0,
	DATE_DOC_VERIFY timestamp not null default 0,
	IP varchar(15) null,
	TIME_ZONE_NAME varchar(50) null,
	TIME_ZONE_OFFSET int(18) null,
	ENTITY_ID bigint unsigned null,
	ENTITY_TYPE varchar(20) null,
	PRESET_ID bigint unsigned null,
	ROLE tinyint null default null,
	CONFIGURED tinyint null default null,
	REMINDER_TYPE tinyint default 0,
	REMINDER_LAST_SEND_DATE datetime null default null,
	REMINDER_PLANNED_NEXT_SEND_DATE datetime null default null,
	REMINDER_COMPLETED tinyint default 0,
	REMINDER_START_DATE datetime null default null,
	EMPLOYEE_ID int null default null,
	HCMLINK_JOB_ID int unsigned default null,
	DATE_SEND datetime null default null,
	DATE_STATUS_CHANGED datetime null default null,
	PRIMARY KEY(ID),
	INDEX IX_B_DOCUMENT_ID (DOCUMENT_ID),
	INDEX IX_B_HASH (HASH)
);

create table if not exists b_sign_block
(
	ID int(18) not null auto_increment,
	CODE varchar(50) not null,
	TYPE varchar(20) null,
	BLANK_ID int(18) not null,
	BLANK_POSITION text not null,
	BLANK_STYLE text default null,
	BLANK_DATA text not null,
	PART int(2) not null,
	CREATED_BY_ID int(18) not null,
	MODIFIED_BY_ID int(18) not null,
	DATE_CREATE timestamp null,
	DATE_MODIFY timestamp not null,
	ROLE tinyint null default null,
	PRIMARY KEY(ID),
	INDEX IX_B_BLANK_ID (BLANK_ID),
	INDEX IX_B_PART (BLANK_ID, PART)
);

create table if not exists b_sign_integration_form
(
	ID int(18) not null auto_increment,
	BLANK_ID int(18) not null,
	PART int(2) not null,
	FORM_ID int(18) not null,
	CREATED_BY_ID int(18) not null,
	MODIFIED_BY_ID int(18) not null,
	DATE_CREATE timestamp null,
	DATE_MODIFY timestamp not null,
	PRIMARY KEY(ID),
	INDEX IX_B_BLANK_PART (BLANK_ID, PART)
);

create table if not exists b_sign_documentgenerator_blank
(
	ID bigint unsigned not null auto_increment,
	BLANK_ID bigint unsigned not null,
	DOCUMENT_GENERATOR_TEMPLATE_ID bigint unsigned unique not null,
	INITIATOR  VARCHAR(1024)          null,
	CREATED_AT timestamp default NOW(),
	PRIMARY KEY(ID)
);

create table if not exists `b_sign_permission`
(
   `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
   `ROLE_ID` INT UNSIGNED NOT NULL,
   `PERMISSION_ID` VARCHAR(32) NOT NULL DEFAULT '',
   `VALUE` VARCHAR(32) NOT NULL DEFAULT '',
   PRIMARY KEY (`ID`),
   CONSTRAINT `IX_SIGN_PERMISSION_ROLE_ID_PERMISSION_ID` UNIQUE
       (`ROLE_ID`, `PERMISSION_ID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_service_user`
(
	`USER_ID` BIGINT UNSIGNED NOT NULL,
	`UID` VARCHAR(32) NOT NULL,
	`DATE_CREATE` DATETIME NOT NULL,
	PRIMARY KEY (`USER_ID`),
	UNIQUE INDEX `IX_B_SIGNS_B2E_USERS_UID` (`UID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_file`
(
	`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`ENTITY_TYPE_ID` TINYINT UNSIGNED NOT NULL,
	`ENTITY_ID` BIGINT UNSIGNED NOT NULL,
	`CODE` TINYINT UNSIGNED NOT NULL,
	`FILE_ID` INT UNSIGNED NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE INDEX `IX_B_SIGN_FILE_ENTITY_TYPE_ENTITY_ID_CODE` (`ENTITY_ID`, `ENTITY_TYPE_ID`, `CODE`)
);

CREATE TABLE IF NOT EXISTS `b_sign_blank_resource`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`BLANK_ID` INT(18) NOT NULL,
	`FILE_ID` INT NOT NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_legal_log`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`DOCUMENT_ID` BIGINT UNSIGNED NOT NULL,
	`DOCUMENT_UID` VARCHAR(20) NOT NULL,
	`MEMBER_ID` BIGINT UNSIGNED NULL DEFAULT NULL,
	`MEMBER_UID` VARCHAR(32) NULL DEFAULT NULL,
	`CODE` VARCHAR(50) NOT NULL,
	`DESCRIPTION` TEXT NOT NULL,
	`USER_ID` BIGINT UNSIGNED NULL DEFAULT NULL,
	`DATE_CREATE` DATETIME NOT NULL,
	PRIMARY KEY (`ID`),
	INDEX `IX_B_SIGNSAFE_LEGAL_LOG_DOCUMENT_ID` (`DOCUMENT_ID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_document_required_field`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`DOCUMENT_ID` BIGINT UNSIGNED NOT NULL,
	`TYPE` VARCHAR(20) NOT NULL,
	`ROLE` TINYINT NOT NULL,
	PRIMARY KEY (`ID`),
	INDEX `IX_B_SIGN_REQUIRED_FIELDS_DOCUMENT_ID` (`DOCUMENT_ID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_document_chat`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`CHAT_ID` BIGINT UNSIGNED NOT NULL,
	`DOCUMENT_ID` BIGINT UNSIGNED NOT NULL,
	`TYPE` TINYINT NOT NULL,
	PRIMARY KEY (`ID`),
	INDEX `IX_B_SIGN_CHAT_DOCUMENT_ID` (`DOCUMENT_ID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_document_template`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`UID` CHAR(32) NOT NULL,
	`TITLE` VARCHAR(255) NOT NULL,
	`STATUS` TINYINT NOT NULL,
	`CREATED_BY_ID` INT NOT NULL,
	`MODIFIED_BY_ID` INT NULL,
	`DATE_CREATE` TIMESTAMP NOT NULL,
	`DATE_MODIFY` TIMESTAMP NULL,
	`VISIBILITY` TINYINT NOT NULL DEFAULT 0,
	PRIMARY KEY (`ID`),
	UNIQUE INDEX `UK_B_SIGN_DOCUMENT_TEMPLATE_UID` (`UID`)
);

CREATE TABLE IF NOT EXISTS `b_sign_document_group`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`CREATED_BY_ID` INT NOT NULL,
	`DATE_CREATE` DATETIME NOT NULL,
	`DATE_MODIFY` DATETIME NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS b_sign_node_sync
(
	ID bigint unsigned not null auto_increment,
	DOCUMENT_ID int(18) not null,
	NODE_ID bigint unsigned not null,
	IS_FLAT TINYINT(1) unsigned not null,
	STATUS tinyint(1) unsigned not null,
	PAGE int unsigned not null,
	DATE_CREATE datetime,
	DATE_MODIFY datetime,
	PRIMARY KEY (ID),
	UNIQUE INDEX IX_B_SIGN_NODE_SYNC_DOCUMENT_NODE (DOCUMENT_ID, NODE_ID, IS_FLAT),
	INDEX IX_B_SIGN_NODE_SYNC_DOCUMENT_STATUS (DOCUMENT_ID, STATUS)
);

CREATE TABLE IF NOT EXISTS b_sign_member_node
(
	MEMBER_ID bigint unsigned not null,
	NODE_SYNC_ID bigint unsigned not null,
	USER_ID bigint unsigned not null,
	DOCUMENT_ID int(18) not null,
	DATE_CREATE datetime,
	PRIMARY KEY (MEMBER_ID, NODE_SYNC_ID),
	INDEX IX_B_SIGN_MEMBER_NODE_DOCUMENT_NODE (DOCUMENT_ID, NODE_SYNC_ID)
);


CREATE TABLE IF NOT EXISTS `b_sign_field_value`
(
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`MEMBER_ID` BIGINT UNSIGNED NOT NULL,
	`FIELD_NAME` VARCHAR(255) NOT NULL,
	`VALUE` TEXT not null,
	`DATE_CREATE` DATETIME NOT NULL,
	`DATE_MODIFY` DATETIME NULL,
	PRIMARY KEY (`ID`),
	UNIQUE INDEX `UK_B_SIGN_FIELD_VALUE_FIELD_NAME_MEMBER_ID` (`FIELD_NAME`, `MEMBER_ID`)
);
