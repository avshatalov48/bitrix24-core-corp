CREATE TABLE IF NOT EXISTS `b_documentgenerator_template` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ACTIVE` CHAR(1) NOT NULL DEFAULT 'Y',
  `NAME` varchar(255) NOT NULL,
  `CODE` varchar(50) NULL,
  `REGION` varchar(50) NULL,
  `SORT` INT(10) NOT NULL DEFAULT 500,
  `CREATE_TIME` datetime not NULL,
  `UPDATE_TIME` datetime NULL,
  `CREATED_BY` int(11),
  `UPDATED_BY` int(11),
  `MODULE_ID` varchar(50) NOT NULL,
  `FILE_ID` int(11) NOT NULL,
  `BODY_TYPE` varchar(255) NOT NULL,
  `NUMERATOR_ID` int(11) NULL,
  `WITH_STAMPS` CHAR(1) NOT NULL DEFAULT 'N',
  `PRODUCTS_TABLE_VARIANT` CHAR(7) NOT NULL DEFAULT '',
  `IS_DELETED` CHAR(1) NOT NULL DEFAULT 'N',
  `IS_DEFAULT` CHAR(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_template_provider` (
  `TEMPLATE_ID` int(10) NOT NULL,
  `PROVIDER` varchar(255) NOT NULL,
  unique ux_docgen_templ_ent(TEMPLATE_ID, PROVIDER),
  index ux_docgen_templ_ent_type(PROVIDER)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_template_user` (
  `TEMPLATE_ID` int(10) NOT NULL,
  `ACCESS_CODE` varchar(100),
  unique ux_docgen_templ_users(TEMPLATE_ID, ACCESS_CODE),
  index ux_docgen_templ_users_code(ACCESS_CODE)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_field` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TEMPLATE_ID` int(10) NULL,
  `TITLE` varchar(50) NULL,
  `PLACEHOLDER` varchar(255) NOT NULL,
  `PROVIDER` varchar(255) NULL,
  `PROVIDER_NAME` varchar(255) NULL,
  `VALUE` TEXT NULL,
  `REQUIRED` char(1) NOT NULL DEFAULT 'N',
  `HIDE_ROW` char(1) NOT NULL DEFAULT 'N',
  `TYPE` VARCHAR(255) NULL,
  `CREATE_TIME` datetime not NULL,
  `UPDATE_TIME` datetime NULL,
  `CREATED_BY` int(11),
  `UPDATED_BY` int(11),
  PRIMARY KEY (ID),
  INDEX ix_docgen_field_ph(PLACEHOLDER),
  UNIQUE ux_docgen_field_templ_ph(TEMPLATE_ID, PLACEHOLDER)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_spreadsheet` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FIELD_ID` int(10) NOT NULL,
  `TITLE` varchar(50) NULL,
  `PLACEHOLDER` varchar(255) NULL,
  `PROVIDER_NAME` varchar(255) NULL,
  `VALUE` TEXT NULL,
  `SORT` INT(10) NOT NULL DEFAULT 500,
  PRIMARY KEY (ID),
  INDEX ix_docgen_spr_field(FIELD_ID)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_document` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TITLE` varchar(255) NOT NULL,
  `NUMBER` varchar(255) NOT NULL,
  `TEMPLATE_ID` int(10) NOT NULL,
  `PROVIDER` varchar(255) NOT NULL,
  `VALUE` TEXT NOT NULL,
  `FILE_ID` int(11) NOT NULL,
  `IMAGE_ID` int(11) NULL,
  `PDF_ID` int(11) NULL,
  `CREATE_TIME` datetime not NULL,
  `UPDATE_TIME` datetime not NULL,
  `CREATED_BY` int(11),
  `UPDATED_BY` int(11),
  `VALUES` TEXT NULL,
  PRIMARY KEY (ID),
  INDEX ix_docgen_doc_templ_val(TEMPLATE_ID, VALUE(10)),
  INDEX ix_docgen_doc_val_prov(VALUE(10), PROVIDER),
  INDEX ix_docgen_doc_file(FILE_ID)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_file` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `STORAGE_TYPE` varchar(255) NOT NULL,
  `STORAGE_WHERE` varchar(255) NOT NULL,
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_external_link` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `DOCUMENT_ID` int(10) NOT NULL,
  `HASH` VARCHAR(32) NOT NULL,
  `VIEWED_TIME` datetime NULL,
  PRIMARY KEY (ID),
  INDEX ix_docgen_ext_link_doc(DOCUMENT_ID),
  INDEX ix_docgen_ext_link_hash(HASH)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_region` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TITLE` varchar(255) NOT NULL,
  `LANGUAGE_ID` CHAR(2),
  `FORMAT_DATE` varchar(255),
  `FORMAT_DATETIME` varchar(255),
  `FORMAT_NAME` varchar(255),
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS `b_documentgenerator_region_phrase` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `REGION_ID` int(10) NOT NULL,
  `CODE` varchar(255) NOT NULL,
  `PHRASE` TEXT,
  PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_documentgenerator_role
(
  ID int(11) NOT NULL auto_increment,
  NAME varchar(255) NOT NULL,
  CODE varchar(255) NULL,
  PRIMARY KEY (ID),
  KEY IX_DOCGEN_PERM_CODE (CODE)
);


CREATE TABLE IF NOT EXISTS b_documentgenerator_role_permission
(
  ID int(11) NOT NULL auto_increment,
  ROLE_ID int(11) NOT NULL,
  ENTITY varchar(50) NOT NULL,
  ACTION varchar(50) NOT NULL,
  PERMISSION char(1) NULL,
  PRIMARY KEY (ID),
  KEY IX_DOCGEN_PERM_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_documentgenerator_role_access
(
  ID int(11) NOT NULL auto_increment,
  ROLE_ID int(11) NOT NULL,
  ACCESS_CODE varchar(100) NOT NULL,
  PRIMARY KEY (ID),
  KEY IX_DOCGEN_ACCESS_ROLE_ID (ROLE_ID)
);

CREATE TABLE IF NOT EXISTS b_documentgenerator_document_binding
(
	`ID` int unsigned NOT NULL auto_increment,
	`DOCUMENT_ID` int unsigned NOT NULL,
	`ENTITY_NAME` varchar(255) NOT NULL,
	`ENTITY_ID` int NOT NULL,
	PRIMARY KEY (ID)
);

CREATE TABLE IF NOT EXISTS b_documentgenerator_actualize_queue
(
	`DOCUMENT_ID` int unsigned NOT NULL,
	`ADDED_TIME` datetime NOT NULL,
	`USER_ID` int unsigned NULL,
	PRIMARY KEY (DOCUMENT_ID)
);
