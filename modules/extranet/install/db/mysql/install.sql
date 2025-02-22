CREATE TABLE IF NOT EXISTS b_extranet_user
(
	ID int NOT NULL AUTO_INCREMENT,
	USER_ID int NOT NULL UNIQUE,
	CHARGEABLE varchar(1) NOT NULL DEFAULT 'Y',
	ROLE varchar(15) NOT NULL,
	PRIMARY KEY(ID),
	INDEX IX_B_USER_ID_CHARGEABLE (USER_ID, CHARGEABLE)
);