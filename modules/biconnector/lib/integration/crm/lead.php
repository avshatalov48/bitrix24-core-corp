<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class Lead
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_lead to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_lead table.
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
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$statusSemanticsForSql = [];
		$statusSemantics = \Bitrix\Crm\PhaseSemantics::getAllDescriptions();
		foreach ($statusSemantics as $id => $value)
		{
			if ($id)
			{
				$statusSemanticsForSql[] = 'when #FIELD_NAME# = \'' . $helper->forSql($id) . '\' then \'' . $helper->forSql($value) . '\'';
			}
			else
			{
				$statusSemanticsForSql[] = 'when #FIELD_NAME# is null or #FIELD_NAME# = \'\' then \'' . $helper->forSql($value) . '\'';
			}
		}
		$statusSemanticsSql = 'case ' . implode("\n", $statusSemanticsForSql) . ' else null end';

		//CREATE TABLE b_crm_lead
		$result['crm_lead'] = [
			'TABLE_NAME' => 'b_crm_lead',
			'TABLE_ALIAS' => 'L',
			'FIELDS' => [
				//ID INT (18) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.ID',
					'FIELD_TYPE' => 'int',
				],
				//DATE_MODIFY DATETIME NULL,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_MODIFY',
					'FIELD_TYPE' => 'datetime',
				],
				//DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
				],
				//CREATED_BY_ID INT (18) UNSIGNED NOT NULL,
				'CREATED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.CREATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CREATED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', L.CREATED_BY_ID, \']\'), nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = L.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = L.CREATED_BY_ID',
				],
				//MODIFY_BY_ID INT (18) UNSIGNED DEFAULT NULL,
				'MODIFY_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.MODIFY_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MODIFIED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.MODIFY_BY_ID is null, null, concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = L.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = L.MODIFY_BY_ID',
				],
				'MODIFIED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.MODIFY_BY_ID is null, null, concat_ws(\' \', concat(\'[\', L.MODIFY_BY_ID, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = L.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = L.MODIFY_BY_ID',
				],
				//ASSIGNED_BY_ID INT (18) UNSIGNED DEFAULT NULL,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.ASSIGNED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.ASSIGNED_BY_ID is null, null, concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = L.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = L.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.ASSIGNED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', L.ASSIGNED_BY_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = L.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = L.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY_DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DA.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DA',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data DA ON DA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DA.VALUE_ID = L.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data DA ON DA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DA.VALUE_ID = L.ASSIGNED_BY_ID',
				],
				//OPENED CHAR(1) DEFAULT 'N',
				'OPENED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.OPENED',
					'FIELD_TYPE' => 'string',
				],
				//COMPANY_ID INT (18) UNSIGNED DEFAULT NULL,
				'COMPANY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.COMPANY_ID',
					'FIELD_TYPE' => 'int',
				],
				'COMPANY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'CO.TITLE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = L.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = L.COMPANY_ID',
				],
				'COMPANY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.COMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', L.COMPANY_ID, \']\'), nullif(CO.TITLE, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = L.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = L.COMPANY_ID',
				],
				//CONTACT_ID INT (18) UNSIGNED DEFAULT NULL,
				'CONTACT_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.CONTACT_ID',
					'FIELD_TYPE' => 'int',
				],
				'CONTACT_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.FULL_NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = L.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = L.CONTACT_ID',
				],
				'CONTACT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.CONTACT_ID is null, null, concat_ws(\' \', concat(\'[\', L.CONTACT_ID, \']\'), nullif(C.FULL_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = L.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = L.CONTACT_ID',
				],
				//STATUS_ID VARCHAR (50) DEFAULT NULL,
				'STATUS_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.STATUS_ID',
					'FIELD_TYPE' => 'string',
				],
				'STATUS_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = L.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = L.STATUS_ID',
				],
				'STATUS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.STATUS_ID is null, null, concat_ws(\' \', concat(\'[\', L.STATUS_ID, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = L.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = L.STATUS_ID',
				],
				//STATUS_DESCRIPTION TEXT DEFAULT NULL,
				'STATUS_DESCRIPTION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.STATUS_DESCRIPTION',
					'FIELD_TYPE' => 'string',
				],
				//STATUS_SEMANTIC_ID VARCHAR(3) NULL,
				'STATUS_SEMANTIC_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.STATUS_SEMANTIC_ID',
					'FIELD_TYPE' => 'string',
				],
				'STATUS_SEMANTIC' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'STATUS_SEMANTIC_ID', $statusSemanticsSql),
					'FIELD_TYPE' => 'string',
				],
				//DEPRECATED:PRODUCT_ID VARCHAR (50) DEFAULT NULL,
				/*
				'CATALOG_PRODUCT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.PRODUCT_ID is null, null, concat_ws(\' \', concat(\'[\', E.ID, \']\'), nullif(E.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'E',
					'JOIN' => 'INNER JOIN b_iblock_element E ON E.ID = L.PRODUCT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_iblock_element E ON E.ID = L.PRODUCT_ID',
				],
				*/
				'CRM_PRODUCT_ID' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'P.ID',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
				],
				'CRM_PRODUCT_NAME' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'P.PRODUCT_NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
				],
				'CRM_PRODUCT' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', P.ID, \']\'), nullif(P.PRODUCT_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
				],
				'CRM_PRODUCT_COUNT' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'TRIM(TRAILING \'.\' FROM (TRIM(TRAILING \'0\' FROM P.QUANTITY)))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'L\' AND P.OWNER_ID = L.ID',
				],
				//OPPORTUNITY DECIMAL(18,2) DEFAULT NULL,
				'OPPORTUNITY' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'L.OPPORTUNITY',
					'FIELD_TYPE' => 'double',
				],
				//CURRENCY_ID VARCHAR (50) DEFAULT NULL,
				'CURRENCY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//TODO:OPPORTUNITY_ACCOUNT DECIMAL(18,2) DEFAULT NULL,
				//TODO:ACCOUNT_CURRENCY_ID VARCHAR (50) DEFAULT NULL,
				//SOURCE_ID VARCHAR (50) DEFAULT NULL,
				'SOURCE_ID' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'NO_AGGREGATION',
					'FIELD_NAME' => 'L.SOURCE_ID',
					'FIELD_TYPE' => 'string',
				],
				'SOURCE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'SS.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'SS',
					'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = L.SOURCE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = L.SOURCE_ID',
				],
				'SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(L.SOURCE_ID is null, null, concat_ws(\' \', concat(\'[\', L.SOURCE_ID, \']\'), nullif(SS.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'SS',
					'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = L.SOURCE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = L.SOURCE_ID',
				],
				//SOURCE_DESCRIPTION TEXT DEFAULT NULL,
				'SOURCE_DESCRIPTION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.SOURCE_DESCRIPTION',
					'FIELD_TYPE' => 'string',
				],
				//TITLE VARCHAR (255) DEFAULT NULL,
				'TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.TITLE',
					'FIELD_TYPE' => 'string',
				],
				//FULL_NAME VARCHAR (100) DEFAULT NULL,
				'FULL_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.FULL_NAME',
					'FIELD_TYPE' => 'string',
				],
				//NAME VARCHAR (50) DEFAULT NULL,
				'NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.NAME',
					'FIELD_TYPE' => 'string',
				],
				//LAST_NAME VARCHAR (50) DEFAULT NULL,
				'LAST_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.LAST_NAME',
					'FIELD_TYPE' => 'string',
				],
				//SECOND_NAME VARCHAR (50) DEFAULT NULL,
				'SECOND_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.SECOND_NAME',
					'FIELD_TYPE' => 'string',
				],
				//COMPANY_TITLE VARCHAR (255) DEFAULT NULL,
				'COMPANY_TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.COMPANY_TITLE',
					'FIELD_TYPE' => 'string',
				],
				//POST VARCHAR (255) DEFAULT NULL,
				'POST' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.POST',
					'FIELD_TYPE' => 'string',
				],
				//ADDRESS TEXT DEFAULT NULL,
				'ADDRESS_1' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.ADDRESS_1',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_2' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.ADDRESS_2',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_CITY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.CITY',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_POSTAL_CODE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.POSTAL_CODE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_REGION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.PROVINCE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_PROVINCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.REGION',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_COUNTRY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.COUNTRY',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				'ADDRESS_COUNTRY_CODE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'ADDR.COUNTRY_CODE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'ADDR',
					'JOIN' => 'INNER JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_addr ADDR ON ADDR.ANCHOR_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND ADDR.ANCHOR_ID = L.ID and ADDR.TYPE_ID = \'' . \Bitrix\Crm\EntityAddressType::Primary . '\'',
				],
				//COMMENTS TEXT DEFAULT NULL,
				'COMMENTS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.COMMENTS',
					'FIELD_TYPE' => 'string',
				],
				//TODO:EXCH_RATE DECIMAL(20,4) DEFAULT 1,
				//TODO:WEBFORM_ID INT (18) UNSIGNED DEFAULT NULL,
				//ORIGINATOR_ID VARCHAR(255) NULL,
				'ORIGINATOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.ORIGINATOR_ID',
					'FIELD_TYPE' => 'string',
				],
				//ORIGIN_ID VARCHAR(255) NULL,
				'ORIGIN_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.ORIGIN_ID',
					'FIELD_TYPE' => 'string',
				],
				//DATE_CLOSED DATETIME NULL,
				'DATE_CLOSED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.DATE_CLOSED',
					'FIELD_TYPE' => 'datetime',
				],
				//BIRTHDATE DATE NULL,
				'BIRTHDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.BIRTHDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:BIRTHDAY_SORT INT(1) NOT NULL DEFAULT 1024,
				//HONORIFIC VARCHAR(128) NULL,
				'HONORIFIC' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'L.HONORIFIC',
					'FIELD_TYPE' => 'string',
				],
				//TODO:HAS_PHONE CHAR(1) NULL,
				//TODO:HAS_EMAIL CHAR(1) NULL,
				//TODO:HAS_IMOL CHAR(1) DEFAULT 'N',
				//IS_RETURN_CUSTOMER CHAR(1) NOT NULL DEFAULT 'N',
				'IS_RETURN_CUSTOMER' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'L.IS_RETURN_CUSTOMER',
					'FIELD_TYPE' => 'string',
				],
				//TODO:FACE_ID INT(18) NULL,
				//TODO:SEARCH_CONTENT MEDIUMTEXT NULL,
				//TODO:IS_MANUAL_OPPORTUNITY CHAR(1) DEFAULT 'N',
				'UTM_SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_S.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_S',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_S.ENTITY_ID = L.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_S.ENTITY_ID = L.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
				],
				'UTM_MEDIUM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_M.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_M',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_M.ENTITY_ID = L.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_M.ENTITY_ID = L.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
				],
				'UTM_CAMPAIGN' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_C.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_C',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_C.ENTITY_ID = L.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_C.ENTITY_ID = L.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
				],
				'UTM_CONTENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_CT.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_CT',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_CT.ENTITY_ID = L.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_CT.ENTITY_ID = L.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
				],
				'UTM_TERM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_T.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_T',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_T.ENTITY_ID = L.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Lead . ' AND UTM_T.ENTITY_ID = L.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
				],
				'PHONE' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'PHONE',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_PHONE.VALUE_TYPE, \'] \', FM_PHONE.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_PHONE',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_PHONE  ON FM_PHONE.ENTITY_ID = \'LEAD\' and FM_PHONE.TYPE_ID = \'' . \CCrmFieldMulti::PHONE . '\' AND FM_PHONE.ELEMENT_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_PHONE  ON FM_PHONE.ENTITY_ID = \'LEAD\' and FM_PHONE.TYPE_ID = \'' . \CCrmFieldMulti::PHONE . '\' AND FM_PHONE.ELEMENT_ID = L.ID',
				],
				'WEB' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'WEB',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_WEB.VALUE_TYPE, \'] \', FM_WEB.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_WEB',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_WEB  ON FM_WEB.ENTITY_ID = \'LEAD\' and FM_WEB.TYPE_ID = \'' . \CCrmFieldMulti::WEB . '\' AND FM_WEB.ELEMENT_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_WEB  ON FM_WEB.ENTITY_ID = \'LEAD\' and FM_WEB.TYPE_ID = \'' . \CCrmFieldMulti::WEB . '\' AND FM_WEB.ELEMENT_ID = L.ID',
				],
				'EMAIL' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'EMAIL',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_EMAIL.VALUE_TYPE, \'] \', FM_EMAIL.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_EMAIL',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_EMAIL  ON FM_EMAIL.ENTITY_ID = \'LEAD\' and FM_EMAIL.TYPE_ID = \'' . \CCrmFieldMulti::EMAIL . '\' AND FM_EMAIL.ELEMENT_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_EMAIL  ON FM_EMAIL.ENTITY_ID = \'LEAD\' and FM_EMAIL.TYPE_ID = \'' . \CCrmFieldMulti::EMAIL . '\' AND FM_EMAIL.ELEMENT_ID = L.ID',
				],
				'IM' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'IM',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat(\'[\', FM_IM.VALUE_TYPE, \'] \', FM_IM.VALUE)',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'FM_IM',
					'JOIN' => 'INNER JOIN b_crm_field_multi FM_IM  ON FM_IM.ENTITY_ID = \'LEAD\' and FM_IM.TYPE_ID = \'' . \CCrmFieldMulti::IM . '\' AND FM_IM.ELEMENT_ID = L.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_field_multi FM_IM  ON FM_IM.ENTITY_ID = \'LEAD\' and FM_IM.TYPE_ID = \'' . \CCrmFieldMulti::IM . '\' AND FM_IM.ELEMENT_ID = L.ID',
				],
			],
		];

		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['crm_lead']['DICTIONARY'] = [
				\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT,
			];
		}
		else
		{
			unset($result['crm_lead']['ASSIGNED_BY_DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_lead']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_LEAD_TABLE'] ?: 'crm_lead';
		foreach ($result['crm_lead']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_LEAD_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_LEAD_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
