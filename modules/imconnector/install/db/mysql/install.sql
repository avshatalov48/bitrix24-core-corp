
CREATE TABLE IF NOT EXISTS `b_imconnectors_status` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CONNECTOR` varchar(255) NOT NULL,
  `LINE` varchar(255) NOT NULL,
  `ACTIVE` varchar(1) NOT NULL,
  `CONNECTION` varchar(1) NOT NULL,
  `ERROR` varchar(1) NOT NULL,
  `REGISTER` varchar(1) NOT NULL,
  `DATA` longtext,
  PRIMARY KEY (`ID`),
  INDEX `CONNECTOR_LINE` (`CONNECTOR`(30), `LINE`),
  INDEX `IX_IMCONNECTOR_LINE` (`LINE`)
);

CREATE TABLE IF NOT EXISTS `b_imconnectors_custom_connectors` (
	`ID` int NOT NULL AUTO_INCREMENT,
	`ID_CONNECTOR` varchar(255) null,
	`NAME` varchar(255) null,
	`ICON` mediumtext null,
	`ICON_DISABLED` text null,
	`COMPONENT` text null,
	`DEL_EXTERNAL_MESSAGES` varchar(2) null,
	`EDIT_INTERNAL_MESSAGES` varchar(2) null,
	`DEL_INTERNAL_MESSAGES` varchar(2) null,
	`NEWSLETTER` varchar(2) null,
	`NEED_SYSTEM_MESSAGES` varchar(2) null,
	`NEED_SIGNATURE` varchar(2) null,
	`CHAT_GROUP` varchar(2) null,
	`REST_APP_ID` int null,
	`REST_PLACEMENT_ID` int null,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `UX_B_ID_CONNECTOR` (`ID_CONNECTOR`)
);

CREATE TABLE IF NOT EXISTS `b_imconnectors_info_connectors` (
	`LINE_ID` int(11),
	`DATA` LONGTEXT,
	`EXPIRES` DATETIME,
	`DATA_HASH` varchar(32) NOT NULL,
	PRIMARY KEY (`LINE_ID`)
);

CREATE TABLE IF NOT EXISTS `b_imconnectors_chat_last_message` (
	`ID` int(11) NOT NULL AUTO_INCREMENT,
	`EXTERNAL_CHAT_ID` varchar(255) NOT NULL,
	`CONNECTOR` varchar(255) NOT NULL,
	`EXTERNAL_MESSAGE_ID` varchar(255) NULL,
	PRIMARY KEY (`ID`),
	INDEX `BX_PERF_09012020` (`EXTERNAL_CHAT_ID`(255), `CONNECTOR`(76))
);

CREATE TABLE IF NOT EXISTS `b_imconnectors_delivery_mark` (
	`MESSAGE_ID` INT(11) UNSIGNED NOT NULL,
	`CHAT_ID` INT(11) UNSIGNED NOT NULL,
	`DATE_CREATE` DATETIME NOT NULL,
	PRIMARY KEY (`MESSAGE_ID`),
	INDEX `IX_DELIVERY_MARK_CHAT_ID` (`CHAT_ID`)
);