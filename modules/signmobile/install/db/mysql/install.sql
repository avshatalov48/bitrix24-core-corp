create table if not exists b_signmobile_notifications
(
	ID BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	USER_ID int(11) NOT NULL DEFAULT 0,
	SIGN_MEMBER_ID BIGINT NOT NULL,
	TYPE SMALLINT UNSIGNED NOT NULL,
    DATE_UPDATE DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY ix_signmobile_notifications_unique_key (USER_ID, TYPE)
);

create table if not exists b_signmobile_notification_queue
(
	ID BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	USER_ID int(11) NOT NULL DEFAULT 0,
	SIGN_MEMBER_ID BIGINT NOT NULL,
	TYPE SMALLINT UNSIGNED NOT NULL,
	DATE_CREATE DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY ix_signmobile_notifications_queue_unique_key (USER_ID, TYPE, SIGN_MEMBER_ID),
	INDEX ix_signmobile_notifications_queue_notification_lifetime (USER_ID, TYPE, DATE_CREATE)
);