
CREATE TABLE b_timeman_entries (
  ID serial NOT NULL,
  TIMESTAMP_X timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  USER_ID int NULL,
  MODIFIED_BY int NULL,
  ACTIVE char(1) NULL DEFAULT 'Y',
  PAUSED char(1) NULL DEFAULT 'N',
  DATE_START timestamp NULL,
  RECORDED_START_TIMESTAMP int NOT NULL DEFAULT 0,
  ACTUAL_START_TIMESTAMP int NOT NULL DEFAULT 0,
  START_OFFSET int NOT NULL DEFAULT 0,
  DATE_FINISH timestamp NULL,
  RECORDED_STOP_TIMESTAMP int NOT NULL DEFAULT 0,
  ACTUAL_STOP_TIMESTAMP int NOT NULL DEFAULT 0,
  STOP_OFFSET int NOT NULL DEFAULT 0,
  TIME_START int NULL,
  TIME_FINISH int NULL,
  DURATION int NULL DEFAULT 0,
  RECORDED_DURATION int NULL DEFAULT 0,
  TIME_LEAKS int NULL DEFAULT 0,
  ACTUAL_BREAK_LENGTH int NOT NULL DEFAULT 0,
  TASKS text NULL,
  IP_OPEN varchar(50) NULL DEFAULT '',
  IP_CLOSE varchar(50) NULL DEFAULT '',
  FORUM_TOPIC_ID int NULL,
  LAT_OPEN double precision NULL,
  LON_OPEN double precision NULL,
  LAT_CLOSE double precision NULL,
  LON_CLOSE double precision NULL,
  CURRENT_STATUS varchar(50) NOT NULL DEFAULT '',
  SCHEDULE_ID int NOT NULL DEFAULT 0,
  SHIFT_ID int NOT NULL DEFAULT 0,
  APPROVED smallint NOT NULL DEFAULT 1,
  APPROVED_BY int NOT NULL DEFAULT 0,
  AUTO_CLOSING_AGENT_ID int NOT NULL DEFAULT 0,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_entries_user_id_date_start ON b_timeman_entries (user_id, date_start);
CREATE INDEX ix_b_timeman_entries_lat_open_lon_open ON b_timeman_entries (lat_open, lon_open);
CREATE INDEX ix_b_timeman_entries_lat_close_lon_close ON b_timeman_entries (lat_close, lon_close);
CREATE INDEX ix_b_timeman_entries_active_date_start_date_finish_user_id ON b_timeman_entries (active, date_start, date_finish, user_id);
CREATE INDEX ix_b_timeman_entries_date_start_date_finish_user_id ON b_timeman_entries (date_start, date_finish, user_id);
CREATE INDEX ix_b_timeman_entries_user_id_recorded_start_timestamp ON b_timeman_entries (user_id, recorded_start_timestamp);

CREATE TABLE b_timeman_reports (
  ID serial NOT NULL,
  TIMESTAMP_X timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  ENTRY_ID int NOT NULL,
  USER_ID int NOT NULL,
  ACTIVE char(1) NULL DEFAULT 'Y',
  REPORT_TYPE varchar(50) NULL DEFAULT 'REPORT',
  REPORT text NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_reports_entry_id_report_type_active ON b_timeman_reports (entry_id, report_type, active);

CREATE TABLE b_timeman_report_daily (
  ID serial NOT NULL,
  TIMESTAMP_X timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  ACTIVE char(1) NULL DEFAULT 'Y',
  USER_ID int NOT NULL,
  ENTRY_ID int NOT NULL,
  REPORT_DATE timestamp NULL,
  TASKS text NULL DEFAULT NULL,
  EVENTS text NULL DEFAULT NULL,
  REPORT text NULL,
  MARK int NULL DEFAULT 0,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_report_daily_entry_id ON b_timeman_report_daily (entry_id);
CREATE INDEX ix_b_timeman_report_daily_user_id_report_date ON b_timeman_report_daily (user_id, report_date);

CREATE TABLE b_timeman_report_full (
  ID serial NOT NULL,
  TIMESTAMP_X timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  ACTIVE char(1) NULL DEFAULT 'Y',
  USER_ID int NOT NULL,
  REPORT_DATE timestamp NULL DEFAULT NULL,
  DATE_FROM timestamp NULL DEFAULT NULL,
  DATE_TO timestamp NULL DEFAULT NULL,
  TASKS text NULL DEFAULT NULL,
  EVENTS text NULL DEFAULT NULL,
  FILES text NULL,
  REPORT text NULL,
  PLANS text NULL,
  MARK char(1) NULL DEFAULT 'N',
  APPROVE char(1) NULL DEFAULT 'N',
  APPROVE_DATE timestamp NULL DEFAULT NULL,
  APPROVER int NULL,
  FORUM_TOPIC_ID int NOT NULL DEFAULT 0,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_report_full_user_id ON b_timeman_report_full (user_id);
CREATE INDEX ix_b_timeman_report_full_active_date_from ON b_timeman_report_full (active, date_from);

CREATE TABLE b_timeman_absence (
  ID serial NOT NULL,
  USER_ID int NOT NULL,
  ENTRY_ID int NULL DEFAULT 0,
  TYPE varchar(255) NULL,
  DATE_START timestamp NULL,
  DATE_FINISH timestamp NULL,
  TIME_START int NULL,
  TIME_FINISH int NULL,
  SOURCE_START varchar(255) NULL,
  SOURCE_FINISH varchar(255) NULL,
  DURATION int NULL DEFAULT 0,
  ACTIVE char(1) NULL DEFAULT 'Y',
  REPORT_TYPE varchar(255) NULL DEFAULT 'PRIVATE',
  REPORT_TEXT text NULL,
  REPORT_CALENDAR_ID int NULL DEFAULT 0,
  SYSTEM_TEXT text NULL,
  IP_START varchar(50) NULL DEFAULT '',
  IP_FINISH varchar(50) NULL DEFAULT '',
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_absence_user_id_active_date_start_type ON b_timeman_absence (user_id, active, date_start, type);
CREATE INDEX ix_b_timeman_absence_user_id_date_start_date_finish ON b_timeman_absence (user_id, date_start, date_finish);

CREATE TABLE b_timeman_work_calendar (
  ID serial NOT NULL,
  NAME varchar(100) NOT NULL DEFAULT '',
  PARENT_CALENDAR_ID int NOT NULL DEFAULT '0',
  SYSTEM_CODE varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (ID)
);

CREATE TABLE b_timeman_work_calendar_exclusion (
  CALENDAR_ID int NOT NULL DEFAULT '0',
  YEAR smallint NOT NULL DEFAULT '0',
  DATES text NOT NULL,
  PRIMARY KEY (CALENDAR_ID, YEAR)
);

CREATE TABLE b_timeman_work_schedule (
  ID serial NOT NULL,
  NAME varchar(255) NOT NULL,
  SCHEDULE_TYPE varchar(25) NOT NULL DEFAULT '',
  CREATED_AT timestamp NOT NULL,
  CREATED_BY int NOT NULL DEFAULT '0',
  UPDATED_BY int NOT NULL DEFAULT '0',
  REPORT_PERIOD varchar(25) NOT NULL DEFAULT '',
  REPORT_PERIOD_OPTIONS text NOT NULL,
  CALENDAR_ID int NOT NULL DEFAULT '0',
  DELETED smallint NOT NULL DEFAULT '0',
  IS_FOR_ALL_USERS smallint NOT NULL DEFAULT '0',
  CONTROLLED_ACTIONS smallint NOT NULL DEFAULT '3',
  ALLOWED_DEVICES varchar(255) NOT NULL DEFAULT '',
  WORKTIME_RESTRICTIONS text NOT NULL,
  DELETED_BY int NOT NULL DEFAULT '0',
  DELETED_AT varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (ID)
);

CREATE TABLE b_timeman_work_schedule_department (
  SCHEDULE_ID int NOT NULL DEFAULT '0',
  DEPARTMENT_ID int NOT NULL DEFAULT '0',
  STATUS smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (SCHEDULE_ID, DEPARTMENT_ID)
);

CREATE TABLE b_timeman_work_schedule_user (
  SCHEDULE_ID int NOT NULL DEFAULT '0',
  USER_ID int NOT NULL DEFAULT '0',
  STATUS smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (SCHEDULE_ID, USER_ID)
);

CREATE TABLE b_timeman_work_shift (
  ID serial NOT NULL,
  NAME varchar(100) NOT NULL,
  BREAK_DURATION int NOT NULL,
  WORK_TIME_START int NOT NULL,
  WORK_TIME_END int NOT NULL,
  SCHEDULE_ID int NOT NULL,
  WORK_DAYS char(7) NOT NULL DEFAULT '',
  DELETED smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_work_shift_schedule_id ON b_timeman_work_shift (schedule_id);

CREATE TABLE b_timeman_work_shift_plan (
  ID serial NOT NULL,
  SHIFT_ID int NOT NULL DEFAULT '0',
  DATE_ASSIGNED date NOT NULL,
  USER_ID int NOT NULL DEFAULT '0',
  DELETED smallint NOT NULL DEFAULT '0',
  CREATED_AT int NOT NULL DEFAULT '0',
  DELETED_AT int NOT NULL DEFAULT '0',
  MISSED_SHIFT_AGENT_ID int NOT NULL DEFAULT '0',
  PRIMARY KEY (ID)
);
CREATE UNIQUE INDEX ux_b_timeman_work_shift_plan_shift_id_user_id_date_assigned ON b_timeman_work_shift_plan (shift_id, user_id, date_assigned);
CREATE INDEX ix_b_timeman_work_shift_plan_user_id_deleted_date_assigned ON b_timeman_work_shift_plan (user_id, deleted, date_assigned);

CREATE TABLE b_timeman_work_time_event_log (
  ID serial NOT NULL,
  USER_ID int NOT NULL DEFAULT '0',
  EVENT_TYPE varchar(50) NOT NULL DEFAULT '',
  EVENT_SOURCE varchar(50) NOT NULL DEFAULT '',
  ACTUAL_TIMESTAMP int NOT NULL DEFAULT '0',
  RECORDED_VALUE int NOT NULL DEFAULT '0',
  RECORDED_OFFSET int NOT NULL DEFAULT '0',
  WORKTIME_RECORD_ID int NOT NULL DEFAULT '0',
  REASON text NOT NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_work_time_event_log_worktime_record_id ON b_timeman_work_time_event_log (worktime_record_id);

CREATE TABLE b_timeman_task_access_code (
  TASK_ID int NOT NULL,
  ACCESS_CODE varchar(100) NOT NULL,
  PRIMARY KEY (TASK_ID, ACCESS_CODE)
);

CREATE TABLE b_timeman_work_schedule_violation_rules (
  ID serial NOT NULL,
  SCHEDULE_ID int NOT NULL DEFAULT '0',
  ENTITY_CODE varchar(255) NOT NULL DEFAULT 'UA',
  MAX_EXACT_START int NOT NULL DEFAULT '-1',
  MIN_EXACT_END int NOT NULL DEFAULT '-1',
  MAX_OFFSET_START int NOT NULL DEFAULT '-1',
  MIN_OFFSET_END int NOT NULL DEFAULT '-1',
  RELATIVE_START_FROM int NOT NULL DEFAULT '-1',
  RELATIVE_START_TO int NOT NULL DEFAULT '-1',
  RELATIVE_END_FROM int NOT NULL DEFAULT '-1',
  RELATIVE_END_TO int NOT NULL DEFAULT '-1',
  MIN_DAY_DURATION int NOT NULL DEFAULT '-1',
  MAX_ALLOWED_TO_EDIT_WORK_TIME int NOT NULL DEFAULT '-1',
  MAX_WORK_TIME_LACK_FOR_PERIOD int NOT NULL DEFAULT '-1',
  PERIOD_TIME_LACK_AGENT_ID int NOT NULL DEFAULT '0',
  MAX_SHIFT_START_DELAY int NOT NULL DEFAULT '-1',
  MISSED_SHIFT_START int NOT NULL DEFAULT '-1',
  USERS_TO_NOTIFY text NULL,
  PRIMARY KEY (ID)
);
CREATE UNIQUE INDEX ux_b_timeman_work_schedule_violation_rules_schedule_id_entity_c ON b_timeman_work_schedule_violation_rules (schedule_id, entity_code);

CREATE TABLE b_timeman_monitor_entity (
  ID serial NOT NULL,
  TYPE varchar(100) NOT NULL,
  TITLE varchar(2000) NOT NULL,
  PUBLIC_CODE varchar(32) NULL DEFAULT null,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_monitor_entity_public_code ON b_timeman_monitor_entity (public_code);

CREATE TABLE b_timeman_monitor_user_log (
  ID serial NOT NULL,
  DATE_LOG date NOT NULL,
  USER_ID int NOT NULL,
  PRIVATE_CODE varchar(40) NOT NULL,
  ENTITY_ID int NOT NULL,
  TIME_SPEND int NOT NULL DEFAULT 0,
  DESKTOP_CODE varchar(32) NOT NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_monitor_user_log_user_id_date_log_desktop_code ON b_timeman_monitor_user_log (user_id, date_log, desktop_code);

CREATE TABLE b_timeman_monitor_comment (
  ID serial NOT NULL,
  USER_LOG_ID int NOT NULL,
  USER_ID int NOT NULL,
  COMMENT text NULL DEFAULT '',
  PRIMARY KEY (ID)
);

CREATE TABLE b_timeman_monitor_absence (
  ID serial NOT NULL,
  USER_LOG_ID int NOT NULL,
  TIME_START timestamp NOT NULL,
  TIME_FINISH timestamp NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_monitor_absence_user_log_id ON b_timeman_monitor_absence (user_log_id);

CREATE TABLE b_timeman_monitor_user_chart (
  ID serial NOT NULL,
  DATE_LOG date NOT NULL,
  USER_ID int NOT NULL,
  DESKTOP_CODE varchar(32) NOT NULL,
  GROUP_TYPE varchar(100) NOT NULL,
  TIME_START timestamp NOT NULL,
  TIME_FINISH timestamp NOT NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_monitor_user_chart_date_log_user_id_desktop_code ON b_timeman_monitor_user_chart (date_log, user_id, desktop_code);

CREATE TABLE b_timeman_monitor_report_comment (
  ID serial NOT NULL,
  DATE_LOG date NOT NULL,
  USER_ID int NOT NULL,
  DESKTOP_CODE varchar(32) NULL DEFAULT null,
  COMMENT text NULL DEFAULT '',
  PRIMARY KEY (ID)
);
CREATE INDEX ix_b_timeman_monitor_report_comment_user_id_date_log_desktop_co ON b_timeman_monitor_report_comment (user_id, date_log, desktop_code);
