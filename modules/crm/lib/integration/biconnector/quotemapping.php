<?php

namespace Bitrix\Crm\Integration\BiConnector;

class QuoteMapping
{
	public static function getMapping(): array
	{
		return [
			'TABLE_NAME' => 'b_crm_quote',
			'TABLE_ALIAS' => 'Q',
			'FIELDS' => [
				//    ID int unsigned auto_increment primary key,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'Q.ID',
					'FIELD_TYPE' => 'int',
				],
				//    DATE_CREATE datetime not null,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
				],
				//    DATE_MODIFY datetime not null,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.DATE_MODIFY',
					'FIELD_TYPE' => 'datetime',
				],
				//    CREATED_BY_ID int unsigned not null,,
				'CREATED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.CREATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CREATED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = Q.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = Q.CREATED_BY_ID',
				],
				'CREATED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', Q.CREATED_BY_ID, \']\'), nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = Q.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = Q.CREATED_BY_ID',
				],
				//    MODIFY_BY_ID int unsigned null,
				'MODIFY_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.MODIFY_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MODIFIED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.MODIFY_BY_ID is null, null, concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = Q.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = Q.MODIFY_BY_ID',
				],
				'MODIFIED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.MODIFY_BY_ID is null, null, concat_ws(\' \', concat(\'[\', Q.MODIFY_BY_ID, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = Q.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = Q.MODIFY_BY_ID',
				],
				//    ASSIGNED_BY_ID  int unsigned null,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.ASSIGNED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.ASSIGNED_BY_ID is null, null, concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = Q.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = Q.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.ASSIGNED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', Q.ASSIGNED_BY_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = Q.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = Q.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY_DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'QA.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'QA',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data QA ON QA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND QA.VALUE_ID = Q.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data QA ON QA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND QA.VALUE_ID = Q.ASSIGNED_BY_ID',
				],
				//    OPENED char default 'N' null,
				'OPENED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.OPENED',
					'FIELD_TYPE' => 'string',
				],
				//    LEAD_ID int unsigned null,
				'LEAD_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.LEAD_ID',
					'FIELD_TYPE' => 'int',
				],
				//    DEAL_ID int unsigned null,
				'DEAL_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.DEAL_ID',
					'FIELD_TYPE' => 'int',
				],
				//    COMPANY_ID int unsigned null,
				'COMPANY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.COMPANY_ID',
					'FIELD_TYPE' => 'int',
				],
				'COMPANY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'CO.TITLE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = Q.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = Q.COMPANY_ID',
				],
				'COMPANY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.COMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', Q.COMPANY_ID, \']\'), nullif(CO.TITLE, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = Q.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = Q.COMPANY_ID',
				],
				//    CONTACT_ID int unsigned null,
				'CONTACT_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.CONTACT_ID',
					'FIELD_TYPE' => 'int',
				],
				'CONTACT_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.FULL_NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = Q.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = Q.CONTACT_ID',
				],
				'CONTACT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.CONTACT_ID is null, null, concat_ws(\' \', concat(\'[\', Q.CONTACT_ID, \']\'), nullif(C.FULL_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = Q.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = Q.CONTACT_ID',
				],
				//    PERSON_TYPE_ID int not null,
				'PERSON_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.PERSON_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				//    MYCOMPANY_ID int unsigned null,
				'MYCOMPANY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.MYCOMPANY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MYCOMPANY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'MCO.TITLE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'MCO',
					'JOIN' => 'INNER JOIN b_crm_company MCO ON MCO.ID = Q.MYCOMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company MCO ON MCO.ID = Q.MYCOMPANY_ID',
				],
				'MYCOMPANY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.MYCOMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', Q.MYCOMPANY_ID, \']\'), nullif(MCO.TITLE, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'MCO',
					'JOIN' => 'INNER JOIN b_crm_company MCO ON MCO.ID = Q.MYCOMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company MCO ON MCO.ID = Q.MYCOMPANY_ID',
				],
				//    TITLE varchar(255) null,
				'TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.TITLE',
					'FIELD_TYPE' => 'string',
				],
				//    STATUS_ID varchar(50) null,
				'STATUS_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.STATUS_ID',
					'FIELD_TYPE' => 'string',
				],
				'STATUS_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'QUOTE_STATUS\' and S.STATUS_ID = Q.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'QUOTE_STATUS\' and S.STATUS_ID = Q.STATUS_ID',
				],
				'STATUS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(Q.STATUS_ID is null, null, concat_ws(\' \', concat(\'[\', Q.STATUS_ID, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'QUOTE_STATUS\' and S.STATUS_ID = Q.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'QUOTE_STATUS\' and S.STATUS_ID = Q.STATUS_ID',
				],
				//    CLOSED char default 'N' null,
				'CLOSED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.CLOSED',
					'FIELD_TYPE' => 'string',
				],
				//    OPPORTUNITY decimal(18, 2) null,
				'OPPORTUNITY' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.OPPORTUNITY',
					'FIELD_TYPE' => 'double',
				],
				//    IS_MANUAL_OPPORTUNITY char           default 'N'               null,
				//    TAX_VALUE decimal(18, 2) null,
				'TAX_VALUE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.TAX_VALUE',
					'FIELD_TYPE' => 'double',
				],
				//    CURRENCY_ID varchar(50) null,
				'CURRENCY_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//    OPPORTUNITY_ACCOUNT   decimal(18, 2)                           null,
				'OPPORTUNITY_ACCOUNT' => [
					'IS_METRIC' => 'N', // 'Y'
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'Q.OPPORTUNITY_ACCOUNT',
					'FIELD_TYPE' => 'double',
				],
				//    TAX_VALUE_ACCOUNT     decimal(18, 2)                           null,
				'TAX_VALUE_ACCOUNT' => [
					'IS_METRIC' => 'N', // 'Y'
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'Q.TAX_VALUE_ACCOUNT',
					'FIELD_TYPE' => 'double',
				],
				//    ACCOUNT_CURRENCY_ID   varchar(50)                              null,
				'ACCOUNT_CURRENCY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.ACCOUNT_CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//    COMMENTS text null,
				'COMMENTS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.COMMENTS',
					'FIELD_TYPE' => 'string',
				],
				//    COMMENTS_TYPE         tinyint unsigned                         null,
				//    BEGINDATE datetime null,
				'BEGINDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.BEGINDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//    CLOSEDATE datetime null,
				'CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'Q.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//    EXCH_RATE             decimal(20, 4) default 1.0000            null,
				//    QUOTE_NUMBER varchar(100) null,
				'QUOTE_NUMBER' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.QUOTE_NUMBER',
					'FIELD_TYPE' => 'string',
				],
				//    CONTENT text null,
				'CONTENT' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.CONTENT',
					'FIELD_TYPE' => 'string',
				],
				//    CONTENT_TYPE          tinyint unsigned                         null,
				//    TERMS                 text                                     null,
				'TERMS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.TERMS',
					'FIELD_TYPE' => 'string',
				],
				//    TERMS_TYPE            tinyint unsigned                         null,
				//    STORAGE_TYPE_ID       tinyint unsigned                         null,
				//    STORAGE_ELEMENT_IDS   text                                     null,
				//    LOCATION_ID           varchar(100)                             null,
				'LOCATION_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'Q.LOCATION_ID',
					'FIELD_TYPE' => 'int',
				],
				//    WEBFORM_ID            int unsigned                             null,
				//    ACTUAL_DATE           date                                     null,
				//    CLIENT_TITLE          varchar(255)                             null,
				//    CLIENT_ADDR           varchar(255)                             null,
				//    CLIENT_CONTACT        varchar(255)                             null,
				//    CLIENT_EMAIL          varchar(255)                             null,
				//    CLIENT_PHONE          varchar(255)                             null,
				//    CLIENT_TP_ID          varchar(255)                             null,
				//    CLIENT_TPA_ID         varchar(255)                             null,
				//    SEARCH_CONTENT        mediumtext                               null,
				//    LAST_ACTIVITY_BY      int unsigned   default '0'               not null,
				//    LAST_ACTIVITY_TIME    datetime       default CURRENT_TIMESTAMP not null,
				'UTM_SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_S.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_S',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_S.ENTITY_ID = Q.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_S.ENTITY_ID = Q.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
				],
				'UTM_MEDIUM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_M.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_M',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_M.ENTITY_ID = Q.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_M.ENTITY_ID = Q.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
				],
				'UTM_CAMPAIGN' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_C.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_C',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_C.ENTITY_ID = Q.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_C.ENTITY_ID = Q.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
				],
				'UTM_CONTENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_CT.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_CT',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_CT.ENTITY_ID = Q.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_CT.ENTITY_ID = Q.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
				],
				'UTM_TERM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_T.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_T',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_T.ENTITY_ID = Q.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Quote . ' AND UTM_T.ENTITY_ID = Q.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
				],
			],
		];
	}
}