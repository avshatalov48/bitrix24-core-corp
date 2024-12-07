
CREATE TABLE b_im_bot_network_session(
	ID int(18) not null auto_increment,
	BOT_ID int(18) default 0,
	DIALOG_ID varchar(50) null,
	SESSION_ID int(18) default 0,
	GREETING_SHOWN char(1) not null default 'N',
	MENU_STATE mediumtext null,
	DATE_CREATE datetime not null default current_timestamp,
	DATE_FINISH datetime null default null,
	DATE_LAST_ACTIVITY datetime null default current_timestamp  ON UPDATE current_timestamp,
	CLOSE_TERM int(18) null DEFAULT 1440,
	TELEMETRY_SENT char(1) not null default 'N',
	STATUS varchar(50) null,
	PRIMARY KEY PK_B_IM_BOT_SESS (ID),
	UNIQUE UX_B_IM_BOT_SESS (BOT_ID, DIALOG_ID)
);
