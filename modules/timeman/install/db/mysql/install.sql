create table if not exists b_timeman_entries
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp not null,
	USER_ID int(18),
	MODIFIED_BY int(18) null,
	ACTIVE char(1) null default 'Y',
	PAUSED char(1) null default 'N',
	DATE_START datetime,
	DATE_FINISH datetime,
	TIME_START int(6),
	TIME_FINISH int(6),
	DURATION int(6) null default 0,
	TIME_LEAKS int(6) null default 0,
	TASKS text null,
	IP_OPEN varchar(50) null default '',
	IP_CLOSE varchar(50) null default '',
	FORUM_TOPIC_ID int(11) null,
	LAT_OPEN DOUBLE null,
	LON_OPEN DOUBLE null,
	LAT_CLOSE DOUBLE null,
	LON_CLOSE DOUBLE null,
	PRIMARY KEY pk_b_timeman_entries (ID),
	INDEX ix_b_timeman_entries_1 (USER_ID, DATE_START),
	INDEX ix_b_timeman_entries_2 (LAT_OPEN, LON_OPEN),
	INDEX ix_b_timeman_entries_3 (LAT_CLOSE, LON_CLOSE),
	INDEX ix_b_timeman_entries_4 (ACTIVE, DATE_START, DATE_FINISH, USER_ID),
	INDEX ix_b_timeman_entries_5 (DATE_START, DATE_FINISH, USER_ID)
);

create table if not exists b_timeman_reports
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp null default CURRENT_TIMESTAMP,
	ENTRY_ID int(11) not null,
	USER_ID int(11) not null,
	ACTIVE char(1) null default 'Y',
	REPORT_TYPE varchar(50) null default 'REPORT',
	REPORT text null,
	PRIMARY KEY pk_b_timeman_reports (ID),
	INDEX ix_b_timeman_reports_1 (ENTRY_ID, REPORT_TYPE, ACTIVE)
);

create table if not exists b_timeman_report_daily
(
	ID int(11) not null auto_increment,
	TIMESTAMP_X timestamp null default CURRENT_TIMESTAMP,
	ACTIVE char(1) null default 'Y',
	USER_ID int(11) not null,
	ENTRY_ID int(11) not null,
	REPORT_DATE datetime,
	TASKS text null,
	EVENTS text null,
	REPORT text null,
	MARK int(5) null default 0,
	PRIMARY KEY pk_b_timeman_report_daily (ID),
	INDEX ix_b_timeman_report_daily_2 (ENTRY_ID),
	INDEX ix_b_timeman_report_daily_3 (USER_ID, REPORT_DATE)
);

CREATE TABLE b_timeman_report_full (
	ID int(11) NOT NULL AUTO_INCREMENT,
	TIMESTAMP_X timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	ACTIVE char(1) DEFAULT 'Y',
	USER_ID int(11) NOT NULL,
	REPORT_DATE datetime DEFAULT NULL,
	DATE_FROM datetime DEFAULT NULL,
	DATE_TO datetime DEFAULT NULL,
	TASKS text,
	EVENTS text,
	FILES text,
	REPORT text,
	PLANS text,
	MARK char(1) DEFAULT 'N',
	APPROVE char(1) DEFAULT 'N',
	APPROVE_DATE datetime DEFAULT NULL,
	APPROVER int(11) NULL,
	FORUM_TOPIC_ID int(11) NOT NULL,
	PRIMARY KEY (ID),
	KEY ix_b_timeman_report_full_1 (USER_ID),
	KEY ix_b_timeman_report_full_2 (ACTIVE, DATE_FROM)
);


CREATE TABLE b_timeman_absence (
	ID int(11) NOT NULL AUTO_INCREMENT,
	USER_ID int(11) NOT NULL,
	ENTRY_ID int(11) NULL default 0,
	TYPE varchar(255) NULL,
	DATE_START datetime NULL,
	DATE_FINISH datetime NULL,
	TIME_START int(6) NULL,
	TIME_FINISH int(6) NULL,
	SOURCE_START varchar(255) NULL,
	SOURCE_FINISH varchar(255) NULL,
	DURATION int(6) NULL default 0,
	ACTIVE char(1) DEFAULT 'Y',
	REPORT_TYPE varchar(255) DEFAULT 'PRIVATE',
	REPORT_TEXT text NULL,
	REPORT_CALENDAR_ID int(11) DEFAULT 0,
	SYSTEM_TEXT text NULL,
	IP_START varchar(50) null default '',
	IP_FINISH varchar(50) null default '',
	PRIMARY KEY (ID),
	KEY ix_b_timeman_absence_1 (USER_ID, ACTIVE, DATE_START, TYPE),
	KEY ix_b_timeman_absence_2 (USER_ID, DATE_START, DATE_FINISH)
);