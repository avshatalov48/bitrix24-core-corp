CREATE TABLE IF NOT EXISTS b_imconnectors_status (
  ID int(11) NOT NULL AUTO_INCREMENT,
  CONNECTOR varchar(255) NOT NULL,
  LINE varchar(255) NOT NULL,
  ACTIVE varchar(1) NOT NULL,
  CONNECTION varchar(1) NOT NULL,
  ERROR varchar(1) NOT NULL,
  REGISTER varchar(1) NOT NULL,
  DATA longtext,
  PRIMARY KEY (ID),
  INDEX CONNECTOR_LINE (CONNECTOR(30), LINE)
);

CREATE TABLE IF NOT EXISTS b_imconnectors_botframework (
  ID int(11) NOT NULL AUTO_INCREMENT,
  VIRTUAL_CONNECTOR varchar(255) NOT NULL,
  ID_CHAT varchar(255) NOT NULL,
  ID_MESSAGE varchar(255) DEFAULT NULL,
  DATA text,
  PRIMARY KEY (ID),
  INDEX IDCHAT_VIRTUALCONNECTOR (ID_CHAT(166),VIRTUAL_CONNECTOR(166))
);

CREATE TABLE IF NOT EXISTS b_imconnectors_custom_connectors (
	ID int auto_increment
		primary key,
	ID_CONNECTOR varchar(255) null,
	NAME varchar(255) null,
	ICON mediumtext null,
	ICON_DISABLED text null,
	COMPONENT text null,
	DEL_EXTERNAL_MESSAGES varchar(2) null,
	EDIT_INTERNAL_MESSAGES varchar(2) null,
	DEL_INTERNAL_MESSAGES varchar(2) null,
	NEWSLETTER varchar(2) null,
	NEED_SYSTEM_MESSAGES varchar(2) null,
	NEED_SIGNATURE varchar(2) null,
	CHAT_GROUP varchar(2) null,
	REST_APP_ID int null,
	REST_PLACEMENT_ID int null,
	constraint UX_B_ID_CONNECTOR
		unique (ID_CONNECTOR)
);

CREATE TABLE IF NOT EXISTS b_imconnectors_info_connectors(
    LINE_ID int(11) PRIMARY KEY,
    DATA LONGTEXT,
    EXPIRES DATETIME,
    DATA_HASH varchar(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS b_imconnectors_chat_last_message(
    ID int(11) auto_increment PRIMARY KEY,
    EXTERNAL_CHAT_ID varchar(255) NOT NULL,
    CONNECTOR varchar(255) NOT NULL,
    EXTERNAL_MESSAGE_ID varchar(255) NULL,
    INDEX BX_PERF_09012020 (EXTERNAL_CHAT_ID(255), CONNECTOR(76))
);