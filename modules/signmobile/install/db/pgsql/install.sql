create table if not exists b_signmobile_notifications
(
	ID int8 GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY,
	USER_ID int NOT NULL DEFAULT 0,
	SIGN_MEMBER_ID int8 NOT NULL,
	TYPE int NOT NULL,
	DATE_UPDATE timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX IF NOT EXISTS ix_signmobile_notifications_unique_key ON b_signmobile_notifications (USER_ID, TYPE);

create table if not exists b_signmobile_notification_queue
(
	ID int8 GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY,
	USER_ID int NOT NULL DEFAULT 0,
	SIGN_MEMBER_ID int8 NOT NULL,
	TYPE int NOT NULL,
	DATE_CREATE timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX IF NOT EXISTS ix_signmobile_notifications_queue_unique_key ON b_signmobile_notification_queue (USER_ID, TYPE, SIGN_MEMBER_ID);
CREATE INDEX IF NOT EXISTS ix_signmobile_notifications_queue_notification_lifetime ON b_signmobile_notification_queue (USER_ID, TYPE, DATE_CREATE);