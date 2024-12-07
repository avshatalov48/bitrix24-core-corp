create table if not exists b_stafftrack_shift (
	ID int auto_increment not null,
	USER_ID int not null,
	SHIFT_DATE date not null,
	DATE_CREATE datetime not null default now(),
	STATUS tinyint not null,
	LOCATION text null,
	PRIMARY KEY (ID),
	INDEX ix_st_shift_user_id (USER_ID),
	INDEX ix_st_shift_date (SHIFT_DATE),
	INDEX ix_st_shift_status (STATUS)
);

create table if not exists b_stafftrack_shift_geo (
	ID int auto_increment not null,
	SHIFT_ID int not null,
	IMAGE_URL text not null,
	ADDRESS text not null,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_st_shift_geo_shift_id (SHIFT_ID)
);

create table if not exists b_stafftrack_shift_cancellation (
	ID int auto_increment not null,
	SHIFT_ID int not null,
	REASON text not null,
	DATE_CANCEL datetime not null default now(),
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_st_shift_cancellation_shift_id (SHIFT_ID)
);

create table if not exists b_stafftrack_option (
	ID int auto_increment not null,
	USER_ID int not null,
	NAME varchar(255) not null,
	VALUE varchar(255) not null,
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_st_option_user_id_name (USER_ID, NAME)
);

create table if not exists b_stafftrack_counter (
	ID int auto_increment not null,
	USER_ID int not null,
	MUTE_STATUS tinyint not null default 0,
	MUTE_UNTIL datetime not null default now(),
	PRIMARY KEY (ID),
	UNIQUE INDEX ux_st_counter_user_id (USER_ID)
);

create table if not exists b_stafftrack_handled_chat (
	ID int auto_increment not null,
	CHAT_ID int not null,
	PRIMARY KEY (ID),
	INDEX ix_st_handled_chat_chat (CHAT_ID)
);

create table if not exists b_stafftrack_user_statistics_hash (
	ID int auto_increment not null,
	USER_ID int not null,
	HASH varchar(64) not null,
	PRIMARY KEY (ID),
	UNIQUE INDEX ix_st_user_statistics_hash_user_id (USER_ID)
);

create table if not exists b_stafftrack_shift_message (
	ID int auto_increment not null,
	SHIFT_ID int not null,
	MESSAGE_ID int not null,
	PRIMARY KEY (ID),
	INDEX ix_st_shift_message_shift_id (SHIFT_ID)
);
