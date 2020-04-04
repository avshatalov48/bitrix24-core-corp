CREATE TABLE IF NOT EXISTS `b_salescenter_page` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NULL,
  `URL` varchar(255) NULL,
  `LANDING_ID` int(10) NULL,
  `HIDDEN` char(1) NOT NULL DEFAULT 'N',
  `IS_WEBFORM` char(1) NOT NULL DEFAULT 'N',
  `IS_FRAME_DENIED` char(1) NOT NULL DEFAULT 'N',
  `SORT` INT(10) NOT NULL DEFAULT 500,
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS `b_salescenter_meta` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HASH` varchar(8) NOT NULL,
  `HASH_CRC` int(18) not null,
  `USER_ID` int(10) NOT NULL,
  `META` TEXT NULL,
  `META_CRC` int(18) not null,
  PRIMARY KEY (ID),
  UNIQUE INDEX ix_salescenter_meta_hash(HASH),
  INDEX ix_salescenter_meta_crc(META_CRC)
);

CREATE TABLE IF NOT EXISTS `b_salescenter_page_param` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PAGE_ID` int NOT NULL,
  `FIELD` varchar(255) NOT NULL,
  PRIMARY KEY(`ID`),
  INDEX ix_salescenter_page_page_id(PAGE_ID)
);