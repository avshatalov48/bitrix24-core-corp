<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class Company
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_company to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_company table.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}

		$params = $event->getParameters();
		//$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$result['crm_company'] = [
			'TABLE_NAME' => 'b_crm_company',
			'TABLE_ALIAS' => 'C',
			'FIELDS' => [
				//ID INT (18) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'C.ID',
					'FIELD_TYPE' => 'int',
				],
				//DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
				],
				//DATE_MODIFY DATETIME NULL,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.DATE_MODIFY',
					'FIELD_TYPE' => 'datetime',
				],
				//CREATED_BY_ID INT (18) UNSIGNED NOT NULL,
				'CREATED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.CREATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CREATED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = C.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = C.CREATED_BY_ID',
				],
				'CREATED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', C.CREATED_BY_ID, \']\'), nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = C.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = C.CREATED_BY_ID',
				],
				//MODIFY_BY_ID INT (18) UNSIGNED DEFAULT NULL,
				'MODIFY_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.MODIFY_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MODIFIED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.MODIFY_BY_ID is null, null, concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = C.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = C.MODIFY_BY_ID',
				],
				'MODIFIED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.MODIFY_BY_ID is null, null, concat_ws(\' \', concat(\'[\', C.MODIFY_BY_ID, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = C.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = C.MODIFY_BY_ID',
				],
				//ASSIGNED_BY_ID INT (1) UNSIGNED DEFAULT NULL,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.ASSIGNED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.ASSIGNED_BY_ID is null, null, concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = C.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = C.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.ASSIGNED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', C.ASSIGNED_BY_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = C.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = C.ASSIGNED_BY_ID',
				],
				//OPENED CHAR(1) DEFAULT 'N',
				'OPENED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.OPENED',
					'FIELD_TYPE' => 'string',
				],
				//TITLE varchar(255) DEFAULT NULL,
				'TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.TITLE',
					'FIELD_TYPE' => 'string',
				],
				//TODO:LOGO VARCHAR (10) DEFAULT NULL,
				//ADDRESS text DEFAULT NULL,
				'ADDRESS_1' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.ADDRESS_1',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_2' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.ADDRESS_2',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_CITY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.CITY',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_POSTAL_CODE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.POSTAL_CODE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_REGION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.PROVINCE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_PROVINCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.REGION',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_COUNTRY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.COUNTRY',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_COUNTRY_CODE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.COUNTRY_CODE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND ADDR.ANCHOR_ID = C.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				//TODO:ADDRESS_LEGAL_LEGAL text DEFAULT NULL,
				//BANKING_DETAILS text DEFAULT NULL,
				'BANKING_DETAILS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.BANKING_DETAILS',
					'FIELD_TYPE' => 'string',
				],
				//COMMENTS text DEFAULT NULL,
				'COMMENTS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.COMMENTS',
					'FIELD_TYPE' => 'string',
				],
				//COMPANY_TYPE varchar(50) DEFAULT NULL,
				'COMPANY_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.COMPANY_TYPE',
					'FIELD_TYPE' => 'string',
				],
				'COMPANY_TYPE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'COMPANY_TYPE\' and S.STATUS_ID = C.COMPANY_TYPE',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'COMPANY_TYPE\' and S.STATUS_ID = C.COMPANY_TYPE',
				],
				'COMPANY_TYPE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.COMPANY_TYPE is null, null, concat_ws(\' \', concat(\'[\', C.COMPANY_TYPE, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'COMPANY_TYPE\' and S.STATUS_ID = C.COMPANY_TYPE',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'COMPANY_TYPE\' and S.STATUS_ID = C.COMPANY_TYPE',
				],
				//INDUSTRY varchar(50) DEFAULT NULL,
				'INDUSTRY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.INDUSTRY',
					'FIELD_TYPE' => 'string',
				],
				'INDUSTRY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'I.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'I',
					'JOIN' => 'INNER JOIN b_crm_status I ON I.ENTITY_ID = \'INDUSTRY\' and I.STATUS_ID = C.INDUSTRY',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status I ON I.ENTITY_ID = \'INDUSTRY\' and I.STATUS_ID = C.INDUSTRY',
				],
				'INDUSTRY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(C.INDUSTRY is null, null, concat_ws(\' \', concat(\'[\', C.INDUSTRY, \']\'), nullif(I.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'I',
					'JOIN' => 'INNER JOIN b_crm_status I ON I.ENTITY_ID = \'INDUSTRY\' and I.STATUS_ID = C.INDUSTRY',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status I ON I.ENTITY_ID = \'INDUSTRY\' and I.STATUS_ID = C.INDUSTRY',
				],
				//REVENUE varchar(255) DEFAULT NULL,
				'REVENUE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => '(C.REVENUE + 0.0)',
					'FIELD_TYPE' => 'double',
				],
				//CURRENCY_ID varchar(50) DEFAULT NULL,
				'CURRENCY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//TODO:EMPLOYEES varchar(50) DEFAULT NULL,
				'EMPLOYEES' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'E.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'E',
					'JOIN' => 'INNER JOIN b_crm_status E ON E.ENTITY_ID = \'EMPLOYEES\' and E.STATUS_ID = C.EMPLOYEES',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status E ON E.ENTITY_ID = \'EMPLOYEES\' and E.STATUS_ID = C.EMPLOYEES',
				],
				//LEAD_ID int(18) DEFAULT NULL,
				'LEAD_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.LEAD_ID',
					'FIELD_TYPE' => 'int',
				],
				//TODO:WEBFORM_ID INT (18) UNSIGNED DEFAULT NULL,
				//ORIGINATOR_ID VARCHAR(255) NULL,
				'ORIGINATOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.ORIGINATOR_ID',
					'FIELD_TYPE' => 'string',
				],
				//ORIGIN_ID VARCHAR(255) NULL,
				'ORIGIN_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.ORIGIN_ID',
					'FIELD_TYPE' => 'string',
				],
				//ORIGIN_VERSION VARCHAR(255) NULL,
				'ORIGIN_VERSION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.ORIGIN_VERSION',
					'FIELD_TYPE' => 'string',
				],
				//HAS_PHONE CHAR(1) NULL,
				//HAS_EMAIL CHAR(1) NULL,
				//HAS_IMOL CHAR(1) DEFAULT 'N',
				//IS_MY_COMPANY CHAR(1) NOT NULL DEFAULT 'N',
				'IS_MY_COMPANY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.IS_MY_COMPANY',
					'FIELD_TYPE' => 'string',
				],
				//TODO:SEARCH_CONTENT MEDIUMTEXT NULL,
				//TODO:CATEGORY_ID INT UNSIGNED NOT NULL DEFAULT 0,
				'UTM_SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_S.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_S',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_S.ENTITY_ID = C.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_S.ENTITY_ID = C.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
				],
				'UTM_MEDIUM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_M.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_M',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_M.ENTITY_ID = C.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_M.ENTITY_ID = C.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
				],
				'UTM_CAMPAIGN' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_C.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_C',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_C.ENTITY_ID = C.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_C.ENTITY_ID = C.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
				],
				'UTM_CONTENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_CT.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_CT',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_CT.ENTITY_ID = C.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_CT.ENTITY_ID = C.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
				],
				'UTM_TERM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_T.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_T',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_T.ENTITY_ID = C.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Company . ' AND UTM_T.ENTITY_ID = C.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
				],
				'PHONE' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'PHONE',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_PHONE.VALUE_TYPE, \'] \', FM_PHONE.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_PHONE',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_PHONE  ON FM_PHONE.ENTITY_ID = \'COMPANY\' and FM_PHONE.TYPE_ID = \'' . \CCrmFieldMulti::PHONE . '\' AND FM_PHONE.ELEMENT_ID = C.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_PHONE  ON FM_PHONE.ENTITY_ID = \'COMPANY\' and FM_PHONE.TYPE_ID = \'' . \CCrmFieldMulti::PHONE . '\' AND FM_PHONE.ELEMENT_ID = C.ID',
				],
				'WEB' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'WEB',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_WEB.VALUE_TYPE, \'] \', FM_WEB.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_WEB',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_WEB  ON FM_WEB.ENTITY_ID = \'COMPANY\' and FM_WEB.TYPE_ID = \'' . \CCrmFieldMulti::WEB . '\' AND FM_WEB.ELEMENT_ID = C.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_WEB  ON FM_WEB.ENTITY_ID = \'COMPANY\' and FM_WEB.TYPE_ID = \'' . \CCrmFieldMulti::WEB . '\' AND FM_WEB.ELEMENT_ID = C.ID',
				],
				'EMAIL' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'EMAIL',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_EMAIL.VALUE_TYPE, \'] \', FM_EMAIL.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_EMAIL',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_EMAIL  ON FM_EMAIL.ENTITY_ID = \'COMPANY\' and FM_EMAIL.TYPE_ID = \'' . \CCrmFieldMulti::EMAIL . '\' AND FM_EMAIL.ELEMENT_ID = C.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_EMAIL  ON FM_EMAIL.ENTITY_ID = \'COMPANY\' and FM_EMAIL.TYPE_ID = \'' . \CCrmFieldMulti::EMAIL . '\' AND FM_EMAIL.ELEMENT_ID = C.ID',
				],
				'IM' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'IM',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_IM.VALUE_TYPE, \'] \', FM_IM.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_IM',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_IM  ON FM_IM.ENTITY_ID = \'COMPANY\' and FM_IM.TYPE_ID = \'' . \CCrmFieldMulti::IM . '\' AND FM_IM.ELEMENT_ID = C.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_IM  ON FM_IM.ENTITY_ID = \'COMPANY\' and FM_IM.TYPE_ID = \'' . \CCrmFieldMulti::IM . '\' AND FM_IM.ELEMENT_ID = C.ID',
				],
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_company']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_COMPANY_TABLE'] ?: 'crm_company';
		foreach ($result['crm_company']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_COMPANY_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_COMPANY_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
