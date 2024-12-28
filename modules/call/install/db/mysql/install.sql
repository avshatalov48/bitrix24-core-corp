CREATE TABLE IF NOT EXISTS b_call_track
(
	ID int not null auto_increment,
	CALL_ID int not null,
	EXTERNAL_TRACK_ID int null,
	FILE_ID int null,
	DISK_FILE_ID int null,
	DATE_CREATE datetime not null default current_timestamp,
	TYPE varchar(50) null,
	DURATION int null,
	DOWNLOAD_URL text null,
	FILE_NAME varchar(100) null,
	FILE_SIZE bigint null,
	FILE_MIME_TYPE varchar(50) default null,
	DOWNLOADED char(1) not null default 'N',
	TEMP_PATH varchar(255) null,
	PRIMARY KEY (ID),
	KEY IX_CALL_TRACK_CALL (CALL_ID, TYPE),
	KEY IX_CALL_TRACK_ADDED (DATE_CREATE)
);

CREATE TABLE IF NOT EXISTS b_call_ai_task
(
	ID int not null auto_increment,
	CALL_ID int not null,
	TRACK_ID int null,
	OUTCOME_ID int null,
	TYPE varchar(50) null,
	DATE_CREATE datetime not null default current_timestamp,
	DATE_FINISHED datetime null,
	STATUS varchar(32) not null,
	HASH varchar(50) null,
	LANGUAGE_ID char(2) null,
	ERROR_CODE varchar(100) null,
	ERROR_MESSAGE text null,
	PRIMARY KEY (ID),
	KEY IX_CALL_AI_QUEUE_HASH (HASH),
	KEY IX_CALL_AI_QUEUE_CALL (CALL_ID),
	KEY IX_CALL_AI_QUEUE_TRACK (TRACK_ID),
	KEY IX_CALL_AI_QUEUE_OUTCOME (OUTCOME_ID),
	KEY IX_CALL_AI_QUEUE_ADDED (DATE_CREATE)
);

CREATE TABLE IF NOT EXISTS b_call_outcome
(
	ID int not null auto_increment,
	CALL_ID int not null,
	TRACK_ID int null,
	TYPE varchar(50) null,
	DATE_CREATE datetime not null default current_timestamp,
	LANGUAGE_ID char(5) null,
	CONTENT longtext,
	PRIMARY KEY (ID),
	KEY IX_CALL_OUTCOME_CALL (CALL_ID, TYPE),
	KEY IX_CALL_OUTCOME_TRACK (TRACK_ID),
	KEY IX_CALL_OUTCOME_ADDED (DATE_CREATE)
);

CREATE TABLE IF NOT EXISTS b_call_outcome_property
(
	ID int not null auto_increment,
	OUTCOME_ID int not null,
	CODE varchar(100) not null,
	CONTENT longtext,
	PRIMARY KEY (ID),
	KEY IX_CALL_OUTCOME_PROP_OUTCOME (OUTCOME_ID),
	KEY IX_CALL_OUTCOME_PROP_TYPE (OUTCOME_ID, CODE)
);

