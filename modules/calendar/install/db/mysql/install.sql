create table b_calendar_type
(
	XML_ID varchar(255) not null,
	NAME  varchar(255) null,
	DESCRIPTION text null,
	EXTERNAL_ID varchar(100) null,
	ACTIVE char(1) not null default 'Y',
	primary key (XML_ID)
);

create table b_calendar_section
(
	ID int not null auto_increment,
	NAME varchar(255) null,
	XML_ID varchar(100) null,
	EXTERNAL_ID varchar(100) null,
	GAPI_CALENDAR_ID varchar(255) null,
	ACTIVE char(1) not null default 'Y',
	DESCRIPTION text null,
	COLOR varchar(10) null,
	TEXT_COLOR varchar(10) null,
	EXPORT varchar(255) null,
	SORT int not null default 100,
	CAL_TYPE varchar(100) null,
	OWNER_ID int null,
	CREATED_BY int not null,
	PARENT_ID int null,
	DATE_CREATE  datetime null,
	TIMESTAMP_X  datetime null,
	DAV_EXCH_CAL varchar(255) null,
	DAV_EXCH_MOD varchar(255) null,
	CAL_DAV_CON varchar(255) null,
	CAL_DAV_CAL varchar(255) null,
	CAL_DAV_MOD varchar(255) null,
	IS_EXCHANGE char(1) null,
	SYNC_TOKEN varchar(255) null,
	PAGE_TOKEN varchar(255) null,
	EXTERNAL_TYPE varchar(20) null,
	primary key (ID),
	INDEX ix_cal_sect_owner (CAL_TYPE, OWNER_ID),
	INDEX ix_cal_section_page_token (PAGE_TOKEN)
);

create table b_calendar_event
(
  ID int not null auto_increment,
  PARENT_ID int null,
  ACTIVE char(1) not null default 'Y',
  DELETED char(1) not null default 'N',
  CAL_TYPE varchar(100) null,
  OWNER_ID int not null,
  NAME varchar(255) null,
  DATE_FROM datetime null,
  DATE_TO datetime null,
  ORIGINAL_DATE_FROM datetime null,
  TZ_FROM varchar(50) null,
  TZ_TO varchar(50) null,
  TZ_OFFSET_FROM int null,
  TZ_OFFSET_TO int null,
  DATE_FROM_TS_UTC int(18) null,
  DATE_TO_TS_UTC int(18) null,
  DT_SKIP_TIME char(1) null,
  DT_LENGTH bigint null,
  EVENT_TYPE varchar(50) null,
  CREATED_BY int not null,
  DATE_CREATE  datetime null,
  TIMESTAMP_X  datetime null,
  DESCRIPTION text null,
  DT_FROM datetime null, /* deprecated */
  DT_TO datetime null, /* deprecated */
  PRIVATE_EVENT char(10) null,
  ACCESSIBILITY char(10) null,
  IMPORTANCE char(10) null,
  IS_MEETING char(1) null,
  MEETING_STATUS char(1) null, /* H - host, Y-yes, N-no, Q-not answered, M-maybe */
  MEETING_HOST int null,
  MEETING text null,
  LOCATION varchar(255) null,
  REMIND text null,
  COLOR varchar(10) null,
  TEXT_COLOR varchar(10) null,
  RRULE varchar(255) null,
  EXDATE text null,
  DAV_XML_ID varchar(255) null,
  G_EVENT_ID varchar(255) null,
  DAV_EXCH_LABEL varchar(255) null,
  CAL_DAV_LABEL varchar(255) null,
  VERSION varchar(255) null,
  ATTENDEES_CODES text null,
  RECURRENCE_ID int null,
  RELATIONS varchar(255) null,
  SEARCHABLE_CONTENT text null,
  SECTION_ID int null,
  SYNC_STATUS varchar(20) null,
  primary key (ID),
  INDEX ix_cal_event_date_utc (DATE_FROM_TS_UTC, DATE_TO_TS_UTC),
  INDEX ix_cal_event_owner_id_date (OWNER_ID, DATE_FROM_TS_UTC, DATE_TO_TS_UTC),
  INDEX ix_cal_event_parent_id (PARENT_ID),
  INDEX ix_cal_event_created_by (CREATED_BY),
  INDEX ix_cal_event_owner_id_accessibility (ACCESSIBILITY, DATE_FROM_TS_UTC, DATE_TO_TS_UTC),
  INDEX ix_cal_event_recurrence_id (RECURRENCE_ID),
  INDEX ix_cal_google_event_id (G_EVENT_ID),
  INDEX ix_cal_dav_xml_id (DAV_XML_ID),
  INDEX ix_cal_owner_del_date (OWNER_ID, DELETED, DATE_TO_TS_UTC, DATE_FROM_TS_UTC),
  INDEX ix_cal_type_del_date (CAL_TYPE, DELETED, DATE_TO_TS_UTC, DATE_FROM_TS_UTC),
  INDEX ix_event_location (LOCATION),
  INDEX ix_event_section_del (SECTION_ID,DELETED),
  INDEX ix_cal_google_sync_status (SYNC_STATUS)
);

create table b_calendar_event_sect
(
	EVENT_ID int not null,
	SECT_ID int not null,
	REL  char(10) null,
	primary key (EVENT_ID, SECT_ID),
	INDEX ix_cal_event_sect (SECT_ID, EVENT_ID)
);

/* b_calendar_attendees - deprecated */
create table b_calendar_attendees
(
	EVENT_ID int not null,
	USER_KEY varchar(255) not null,
	USER_ID int null,
	USER_EMAIL varchar(255) null,
	USER_NAME varchar(255) null,
	STATUS char(10) not null default 'Q',
	DESCRIPTION varchar(255) null,
	ACCESSIBILITY char(10) null,
	REMIND varchar(255) null,
	SECT_ID int null,
	COLOR varchar(10) null,
	TEXT_COLOR varchar(10) null,
	primary key (EVENT_ID, USER_KEY),
	INDEX ix_cal_attendees_0 (USER_KEY)
);

CREATE TABLE b_calendar_push (
  ENTITY_TYPE varchar(24) NOT NULL,
  ENTITY_ID int(11) NOT NULL,
  CHANNEL_ID varchar(128) NOT NULL,
  RESOURCE_ID varchar(128) NOT NULL,
  EXPIRES datetime NOT NULL,
  NOT_PROCESSED varchar(1) NOT NULL DEFAULT 'N',
  FIRST_PUSH_DATE datetime DEFAULT NULL,
  PRIMARY KEY (ENTITY_TYPE,ENTITY_ID),
  INDEX ix_cal_google_push_expires (EXPIRES)
);

create table b_calendar_access
(
	ACCESS_CODE varchar(100) not null,
	TASK_ID int not null,
	SECT_ID varchar(100) not null,
	primary key (ACCESS_CODE, TASK_ID, SECT_ID)
);

create table b_calendar_resource
(
  ID int not null auto_increment,
  EVENT_ID int null,
  CAL_TYPE varchar(100) null,
  RESOURCE_ID int not null,
  PARENT_TYPE varchar(100) null,
  PARENT_ID int not null,
  UF_ID int null,
  DATE_FROM_UTC datetime null,
  DATE_TO_UTC datetime null,
  DATE_FROM datetime null,
  DATE_TO datetime null,
  DURATION bigint null,
  SKIP_TIME char(1) null,
  TZ_FROM varchar(50) null,
  TZ_TO varchar(50) null,
  TZ_OFFSET_FROM int null,
  TZ_OFFSET_TO int null,
  CREATED_BY int not null,
  DATE_CREATE  datetime null,
  TIMESTAMP_X  datetime null,
  SERVICE_NAME varchar(200) null,
  primary key (ID),
  INDEX ix_ufid_parenttype_parentid (UF_ID, PARENT_TYPE, PARENT_ID)
);

create table b_calendar_location
(
  ID int not null auto_increment,
  SECTION_ID int not null,
  NECESSITY char(1) default 'N',
  CAPACITY int default 0,
  PRIMARY KEY(ID),
  INDEX ix_location_section(SECTION_ID)
);

create table `b_calendar_log`
(
  ID int not null auto_increment,
  TIMESTAMP_X TIMESTAMP NOT NULL DEFAULT current_timestamp,
  MESSAGE TEXT NULL,
  PRIMARY KEY(ID)
)