CREATE TABLE b_controller_group
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	NAME varchar(255) NOT NULL,
	UPDATE_PERIOD int(11) NOT NULL DEFAULT -1,
	DISABLE_DEACTIVATED char(1) NOT NULL DEFAULT 'N',
	DESCRIPTION text,
	MODIFIED_BY int(11),
	DATE_CREATE datetime NOT NULL,
	CREATED_BY int(11),
	INSTALL_INFO text,
	UNINSTALL_INFO text,
	INSTALL_PHP text,
	UNINSTALL_PHP text,
	TRIAL_PERIOD int(18),
	COUNTER_UPDATE_PERIOD int(18),
	CHECK_COUNTER_FREE_SPACE char(1),
	CHECK_COUNTER_SITES char(1),
	CHECK_COUNTER_USERS char(1),
	CHECK_COUNTER_LAST_AUTH char(1),
	PRIMARY KEY pk_b_controller_group(ID)
);

INSERT INTO b_controller_group(ID, NAME, DATE_CREATE, CREATED_BY, MODIFIED_BY) VALUES(1, '(default)', now(), 1, 1);

CREATE TABLE b_controller_member
(
	ID int(11) NOT NULL auto_increment,
	MEMBER_ID varchar(32) NOT NULL,
	SECRET_ID varchar(32) NOT NULL,
	NAME varchar(255) NOT NULL,
	URL varchar(255) NOT NULL,
	HOSTNAME varchar(255),
	EMAIL varchar(255),
	CONTACT_PERSON varchar(255),
	CONTROLLER_GROUP_ID int(11) NOT NULL,
	DISCONNECTED char(1) NOT NULL default 'N',
	SHARED_KERNEL char(1) NOT NULL default 'N',
	ACTIVE char(1) NOT NULL default 'Y',
	DATE_ACTIVE_FROM datetime,
	DATE_ACTIVE_TO datetime,
	SITE_ACTIVE char(1) NOT NULL default 'Y',
	TIMESTAMP_X timestamp NOT NULL,
	MODIFIED_BY int(11),
	DATE_CREATE datetime NOT NULL,
	CREATED_BY int(11),
	IN_GROUP_FROM datetime,
	NOTES text,
	COUNTER_FREE_SPACE double(18, 2),
	COUNTER_SITES int(18),
	COUNTER_USERS int(18),
	COUNTER_LAST_AUTH datetime,
	COUNTERS_UPDATED datetime,
	PRIMARY KEY pk_b_controller_member(ID),
	UNIQUE KEY ux_cntr_memberid(MEMBER_ID),
	KEY ix_active_group(ACTIVE, CONTROLLER_GROUP_ID)
);

CREATE TABLE b_controller_member_log
(
	ID int(11) NOT NULL auto_increment,
	CONTROLLER_MEMBER_ID int(11) NOT NULL,
	USER_ID int(11) NOT NULL,
	CREATED_DATE datetime NOT NULL,
	FIELD varchar(50) NOT NULL,
	FROM_VALUE text,
	TO_VALUE text,
	NOTES text,
	PRIMARY KEY pk_b_controller_member_log(ID),
	KEY ix_b_controller_member_log(CONTROLLER_MEMBER_ID, FIELD, CREATED_DATE)
);

CREATE TABLE b_controller_task
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	DATE_CREATE datetime NOT NULL,
	TASK_ID varchar(50) NOT NULL,
	CONTROLLER_MEMBER_ID int NOT NULL,
	DATE_EXECUTE datetime,
	INIT_EXECUTE text,
	INIT_EXECUTE_PARAMS mediumtext,
	INIT_CRC int(11) NOT NULL default 0,
	UPDATE_PERIOD int(11) NOT NULL default 0,
	RESULT_EXECUTE text,
	STATUS char(1) NOT NULL default 'N',
	INDEX_SALT int NOT NULL default 0,
	PRIMARY KEY (ID),
	INDEX IX_contr_task_exec(DATE_EXECUTE),
	UNIQUE INDEX UX_contr_task(CONTROLLER_MEMBER_ID, TASK_ID, DATE_EXECUTE, INIT_CRC, INDEX_SALT)
);
create index ix_b_controller_task_1 on b_controller_task(STATUS, ID);

CREATE TABLE b_controller_command
(
	ID int(11) NOT NULL auto_increment,
	MEMBER_ID varchar(32) NOT NULL,
	COMMAND_ID varchar(32) NOT NULL,
	DATE_INSERT datetime NOT NULL,
	COMMAND text NOT NULL,
	DATE_EXEC datetime,
	TASK_ID int(11),
	ADD_PARAMS mediumtext,
	PRIMARY KEY pk_b_controller_command(ID),
	UNIQUE INDEX b_contr_comm_ux(MEMBER_ID, COMMAND_ID)
);

CREATE TABLE b_controller_log
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	CONTROLLER_MEMBER_ID int(11) NOT NULL,
	NAME varchar(255) NOT NULL,
	DESCRIPTION longtext,
	TASK_ID int(11),
	USER_ID int(11),
	STATUS char(1) NOT NULL default 'Y',
	PRIMARY KEY pk_b_controller_log(ID),
	INDEX IX_contr_log_member(CONTROLLER_MEMBER_ID),
	INDEX IX_contr_log_task(TASK_ID)
);

CREATE TABLE b_controller_counter
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	COUNTER_TYPE char(1) NOT NULL default 'F',
	COUNTER_FORMAT char(1),
	NAME varchar(255) NOT NULL,
	COMMAND text NOT NULL,
	PRIMARY KEY pk_b_controller_counter (ID)
);

CREATE TABLE b_controller_counter_group
(
	CONTROLLER_GROUP_ID int(11) NOT NULL,
	CONTROLLER_COUNTER_ID int(11) NOT NULL,
	UNIQUE INDEX ux_b_controller_counter_group_1(CONTROLLER_GROUP_ID, CONTROLLER_COUNTER_ID),
	UNIQUE INDEX ux_b_controller_counter_group_2(CONTROLLER_COUNTER_ID, CONTROLLER_GROUP_ID)
);

CREATE TABLE b_controller_counter_value
(
	CONTROLLER_MEMBER_ID int(11) NOT NULL,
	CONTROLLER_COUNTER_ID int(11) NOT NULL,
	VALUE_INT int,
	VALUE_FLOAT double,
	VALUE_DATE datetime,
	VALUE_STRING varchar(255),
	PRIMARY KEY pk_b_controller_counter_value (CONTROLLER_MEMBER_ID, CONTROLLER_COUNTER_ID),
	UNIQUE INDEX ux_b_controller_counter_value(CONTROLLER_COUNTER_ID, CONTROLLER_MEMBER_ID)
);

CREATE TABLE b_controller_group_map
(
	ID int(11) NOT NULL auto_increment,
	CONTROLLER_GROUP_ID int(11),
	REMOTE_GROUP_CODE varchar(30),
	LOCAL_GROUP_CODE varchar(30),
	PRIMARY KEY pk_b_controller_group_map (ID)
);

CREATE TABLE b_controller_auth_log
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	FROM_CONTROLLER_MEMBER_ID int(11),
	TO_CONTROLLER_MEMBER_ID int(11),
	TYPE varchar(50),
	STATUS char(1) NOT NULL default 'Y',
	USER_ID int(11),
	USER_NAME varchar(255),
	PRIMARY KEY pk_b_controller_auth_log(ID),
	INDEX ix_b_controller_auth_log_0(TIMESTAMP_X),
	INDEX ix_b_controller_auth_log_1(FROM_CONTROLLER_MEMBER_ID),
	INDEX ix_b_controller_auth_log_2(TO_CONTROLLER_MEMBER_ID)
);

CREATE TABLE b_controller_auth_grant
(
	ID int(11) NOT NULL auto_increment,
	TIMESTAMP_X timestamp NOT NULL,
	GRANTED_BY int(11) NOT NULL,
	CONTROLLER_MEMBER_ID int(11) NOT NULL,
	GRANTEE_USER_ID int(11),
	GRANTEE_GROUP_ID int(11),
	ACTIVE char(1) NOT NULL default 'Y',
	SCOPE varchar(20) NOT NULL,
	DATE_START datetime,
	DATE_END datetime,
	NOTE varchar(255),
	PRIMARY KEY pk_b_controller_auth_grant(ID),
	INDEX ix_b_controller_auth_grant_0(CONTROLLER_MEMBER_ID)
);
