CREATE TABLE IF NOT EXISTS `b_transformer_command` (
	`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`GUID` varchar(32) NOT NULL,
	`STATUS` int(10) unsigned NOT NULL DEFAULT 10,
	`COMMAND` varchar(255) NOT NULL,
	`MODULE` TEXT NOT NULL,
	`CALLBACK` TEXT NOT NULL,
	`PARAMS` TEXT NOT NULL,
	`FILE` VARCHAR(255) NULL,
	`ERROR` TEXT NULL,
	`ERROR_CODE` int(10) NULL,
	`UPDATE_TIME` datetime,
	`SEND_TIME` datetime,
	`CONTROLLER_URL` varchar(255),
	PRIMARY KEY (ID),
	unique index ux_b_transformer_command_guid (GUID),
	index ix_trans_commands_file (FILE),
	INDEX ix_trans_time_error (UPDATE_TIME, ERROR (128))
);
