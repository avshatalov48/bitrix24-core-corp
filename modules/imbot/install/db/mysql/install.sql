
CREATE TABLE b_im_bot_network_session(
	ID int(18) not null auto_increment,
	BOT_ID int(18) default 0,
	DIALOG_ID varchar(50) null,
	SESSION_ID int(18) default 0,
	GREETING_SHOWN enum('Y','N') not null default 'N',
	PRIMARY KEY PK_B_IM_BOT_SESS (ID),
	UNIQUE UX_B_IM_BOT_SESS (BOT_ID, DIALOG_ID)
);
