create table if not exists b_meeting
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp null default CURRENT_TIMESTAMP,
	EVENT_ID int(11) null,
	DATE_START datetime null,
	DATE_FINISH datetime null,
	DURATION int(5) null,
	CURRENT_STATE char(1) null default 'P',
	TITLE varchar(255) not null,
	GROUP_ID int(11) null,
	PARENT_ID int(11) null,
	DESCRIPTION text null,
	PLACE varchar(255) null,
	PROTOCOL_TEXT text null,
	PRIMARY KEY pk_b_meeting (ID),
	INDEX ix_b_meeting_1 (GROUP_ID)
);

create table if not exists b_meeting_files
(
	MEETING_ID int(11) not null,
	FILE_ID int(11) not null,
	FILE_SRC int(11) null,
	PRIMARY KEY pk_b_meeting_file (MEETING_ID, FILE_ID),
	INDEX ix_b_meeting_files_1 (FILE_SRC)
);

create table if not exists b_meeting_users
(
	MEETING_ID int(11) not null,
	USER_ID int(18) not null,
	USER_ROLE char(1) null default 'M',
	PRIMARY KEY pk_b_meeting_users (MEETING_ID, USER_ID)
);

create table if not exists b_meeting_item
(
	ID int(11) not null auto_increment,
	TITLE varchar(255) null,
	DESCRIPTION text null,
	PRIMARY KEY pk_b_meeting_item (ID)
);

create table if not exists b_meeting_item_files
(
	ITEM_ID int(11) not null,
	FILE_ID int(11) not null,
	FILE_SRC int(11) null,
	PRIMARY KEY pk_b_meeting_item_files (ITEM_ID, FILE_ID),
	INDEX ix_b_meeting_item_files_1 (FILE_SRC)
);

create table if not exists b_meeting_item_tasks
(
	ITEM_ID int(11) not null,
	INSTANCE_ID int(11) null,
	TASK_ID int(11) not null,
	PRIMARY KEY pk_b_meeting_item_tasks (ITEM_ID, TASK_ID),
	INDEX ix_b_meeting_item_tasks_1 (INSTANCE_ID)
);

create table if not exists b_meeting_instance
(
	ID int(11) not null auto_increment,
	ITEM_ID int(11) not null,
	MEETING_ID int(11) not null,
	INSTANCE_PARENT_ID int(11) null,
	INSTANCE_TYPE char(1) null default 'A',
	ORIGINAL_TYPE char(1) null default 'A',
	SORT int(11) null default 500,
	DURATION int(5) null,
	DEADLINE datetime null,
	TASK_ID int(11) null,
	PRIMARY KEY pk_b_meeting_instance (ID),
	INDEX ix_b_meeting_instance_1 (MEETING_ID),
	INDEX ix_b_meeting_instance_2 (ITEM_ID)
);

create table if not exists b_meeting_instance_users
(
	USER_ID int(18) not null,
	INSTANCE_ID int(11) not null,
	ITEM_ID int(11) not null,
	MEETING_ID int(11) not null,
	PRIMARY KEY pk_b_meeting_instance_users (INSTANCE_ID, USER_ID)
);


create table if not exists b_meeting_reports
(
	ID int(11) not null auto_increment,
	USER_ID int(18) not null,
	INSTANCE_ID int(11) not null,
	ITEM_ID int(11) not null,
	MEETING_ID int(11) not null,
	REPORT text,
	PRIMARY KEY pk_b_meeting_reports (ID),
	UNIQUE ix_b_meeting_reports_1 (INSTANCE_ID, USER_ID)
);

create table if not exists b_meeting_reports_files
(
	FILE_ID int(18) not null,
	FILE_SRC int(11) null,
	REPORT_ID int(11) not null,
	INSTANCE_ID int(11) not null,
	ITEM_ID int(11) not null,
	MEETING_ID int(11) not null,
	PRIMARY KEY pk_b_meeting_reports_files (REPORT_ID, FILE_ID),
	INDEX ix_b_meeting_reports_files_1 (INSTANCE_ID)
);