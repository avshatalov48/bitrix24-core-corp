CREATE TABLE b_dav_locks (
	ID varchar(128) NOT NULL,
	PATH varchar(255) NOT NULL,
	EXPIRES int NOT NULL,
	LOCK_OWNER varchar(255) NULL,
	LOCK_DEPTH char(1) NOT NULL DEFAULT 'N',
	LOCK_TYPE char(1) NOT NULL DEFAULT 'R',
	LOCK_SCOPE char(1) NOT NULL DEFAULT 'S',
	CREATED datetime NOT NULL,
	MODIFIED datetime NOT NULL,
	primary key (ID),
	index ix_b_gw_locks_path(PATH),
	index ix_b_gw_locks_expires(EXPIRES)
);

CREATE TABLE b_dav_connections (
	ID int NOT NULL auto_increment,
	ENTITY_TYPE varchar(32) NOT NULL DEFAULT 'user',
	ENTITY_ID int NOT NULL,
	ACCOUNT_TYPE varchar(32) NOT NULL,
	SYNC_TOKEN varchar(128) NULL,
	NAME varchar(128) NOT NULL,
	SERVER_SCHEME varchar(5) NOT NULL DEFAULT 'http',
	SERVER_HOST varchar(128) NOT NULL,
	SERVER_PORT int NOT NULL DEFAULT 80,
	SERVER_USERNAME varchar(128) NULL,
	SERVER_PASSWORD varchar(128) NULL,
	SERVER_PATH varchar(255) NOT NULL DEFAULT '/',
	CREATED datetime NOT NULL,
	MODIFIED datetime NOT NULL,
	SYNCHRONIZED datetime NULL,
	LAST_RESULT varchar(128) NULL,
	primary key (ID),
	index ix_b_dav_conns_at(ACCOUNT_TYPE),
	index ix_b_dav_conns_ent(ENTITY_TYPE, ENTITY_ID, ACCOUNT_TYPE)
);

CREATE TABLE b_dav_tokens (
  TOKEN VARCHAR(45),
  USER_ID INT NOT NULL UNIQUE,
  EXPIRED_AT DATETIME NOT NULL,
  PRIMARY KEY (TOKEN)
)