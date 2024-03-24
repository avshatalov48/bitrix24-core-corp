<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class Deal
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_deal to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_deal table.
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

		$result['crm_deal'] = [
			'TABLE_NAME' => 'b_crm_deal',
			'TABLE_ALIAS' => 'D',
			'FIELDS' => [
				//ID INT (18) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'D.ID',
					'FIELD_TYPE' => 'int',
				],
				//DATE_CREATE DATETIME NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.DATE_CREATE',
					'FIELD_TYPE' => 'datetime',
				],
				//DATE_MODIFY DATETIME NULL,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.DATE_MODIFY',
					'FIELD_TYPE' => 'datetime',
				],
				//CREATED_BY_ID INT (18) UNSIGNED NOT NULL,
				'CREATED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CREATED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CREATED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = D.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = D.CREATED_BY_ID',
				],
				'CREATED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', D.CREATED_BY_ID, \']\'), nullif(UC.NAME, \'\'), nullif(UC.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UC',
					'JOIN' => 'INNER JOIN b_user UC ON UC.ID = D.CREATED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UC ON UC.ID = D.CREATED_BY_ID',
				],
				//MODIFY_BY_ID INT (18) UNSIGNED DEFAULT NULL,
				'MODIFY_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.MODIFY_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MODIFIED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.MODIFY_BY_ID is null, null, concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = D.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = D.MODIFY_BY_ID',
				],
				'MODIFIED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.MODIFY_BY_ID is null, null, concat_ws(\' \', concat(\'[\', D.MODIFY_BY_ID, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => 'INNER JOIN b_user UM ON UM.ID = D.MODIFY_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UM ON UM.ID = D.MODIFY_BY_ID',
				],
				//ASSIGNED_BY_ID INT (18) UNSIGNED DEFAULT NULL,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.ASSIGNED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.ASSIGNED_BY_ID is null, null, concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.ASSIGNED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', D.ASSIGNED_BY_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = D.ASSIGNED_BY_ID',
				],
				'ASSIGNED_BY_DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DA.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DA',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data DA ON DA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DA.VALUE_ID = D.ASSIGNED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data DA ON DA.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DA.VALUE_ID = D.ASSIGNED_BY_ID',
				],
				//OPENED CHAR(1) DEFAULT 'N',
				'OPENED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.OPENED',
					'FIELD_TYPE' => 'string',
				],
				//LEAD_ID INT (18) DEFAULT NULL,
				'LEAD_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.LEAD_ID',
					'FIELD_TYPE' => 'int',
				],
				//COMPANY_ID INT (18) UNSIGNED DEFAULT NULL,
				'COMPANY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.COMPANY_ID',
					'FIELD_TYPE' => 'int',
				],
				'COMPANY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'CO.TITLE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
				],
				'COMPANY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.COMPANY_ID is null, null, concat_ws(\' \', concat(\'[\', D.COMPANY_ID, \']\'), nullif(CO.TITLE, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'CO',
					'JOIN' => 'INNER JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_company CO ON CO.ID = D.COMPANY_ID',
				],
				//CONTACT_ID INT (18) UNSIGNED DEFAULT NULL,
				'CONTACT_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CONTACT_ID',
					'FIELD_TYPE' => 'int',
				],
				'CONTACT_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'C.FULL_NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
				],
				'CONTACT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.CONTACT_ID is null, null, concat_ws(\' \', concat(\'[\', D.CONTACT_ID, \']\'), nullif(C.FULL_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'C',
					'JOIN' => 'INNER JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_contact C ON C.ID = D.CONTACT_ID',
				],
				//TODO:QUOTE_ID INT(1) UNSIGNED DEFAULT NULL,
				//TITLE VARCHAR (255) DEFAULT NULL,
				'TITLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.TITLE',
					'FIELD_TYPE' => 'string',
				],
				//DEPRECATED:PRODUCT_ID VARCHAR (50) DEFAULT NULL,
				/*
				'CATALOG_PRODUCT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.PRODUCT_ID is null, null, concat_ws(\' \', concat(\'[\', D.PRODUCT_ID, \']\'), nullif(E.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'E',
					'JOIN' => 'INNER JOIN b_iblock_element E ON E.ID = D.PRODUCT_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_iblock_element E ON E.ID = D.PRODUCT_ID',
				],
				*/
				'CRM_PRODUCT_ID' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'P.ID',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
				],
				'CRM_PRODUCT' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', P.ID, \']\'), nullif(P.PRODUCT_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
				],
				'CRM_PRODUCT_COUNT' => [
					'GROUP_CONCAT' => ', ',
					'GROUP_KEY' => 'CRM_PRODUCT_ID',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'TRIM(TRAILING \'.\' FROM (TRIM(TRAILING \'0\' FROM P.QUANTITY)))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'P',
					'JOIN' => 'INNER JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_product_row P ON P.OWNER_TYPE = \'D\' AND P.OWNER_ID = D.ID',
				],
				//CATEGORY_ID INT (18) UNSIGNED NULL,
				'CATEGORY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CATEGORY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CATEGORY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.CATEGORY_ID is null, null, concat_ws(\' \', ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'JOIN' => 'INNER JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
				],
				'CATEGORY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.CATEGORY_ID is null, null, concat_ws(\' \', concat(\'[\', D.CATEGORY_ID, \']\'), ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'JOIN' => 'INNER JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal_category DC ON DC.ID = D.CATEGORY_ID',
				],
				//STAGE_ID VARCHAR (50) DEFAULT NULL,
				'STAGE_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'D.STAGE_ID',
					'FIELD_TYPE' => 'string',
				],
				'STAGE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = D.STAGE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = D.STAGE_ID',
				],
				'STAGE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.STAGE_ID is null, null, concat_ws(\' \', concat(\'[\', D.STAGE_ID, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = D.STAGE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = D.STAGE_ID',
				],
				//TODO:STAGE_SEMANTIC_ID VARCHAR(3) NULL,
				'STAGE_SEMANTIC_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.STAGE_SEMANTIC_ID',
					'FIELD_TYPE' => 'string',
				],
				'STAGE_SEMANTIC' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'STAGE_SEMANTIC_ID', $statusSemanticsSql),
					'FIELD_TYPE' => 'string',
				],
				//IS_NEW CHAR(1) NULL,
				'IS_NEW' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.IS_NEW',
					'FIELD_TYPE' => 'string',
				],
				//IS_RECURRING CHAR(1) DEFAULT 'N',
				'IS_RECURRING' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.IS_RECURRING',
					'FIELD_TYPE' => 'string',
				],
				//IS_RETURN_CUSTOMER CHAR(1) DEFAULT 'N',
				'IS_RETURN_CUSTOMER' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.IS_RETURN_CUSTOMER',
					'FIELD_TYPE' => 'string',
				],
				//TODO:IS_REPEATED_APPROACH CHAR(1) DEFAULT 'N',
				//CLOSED CHAR (1) DEFAULT 'N',
				'CLOSED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CLOSED',
					'FIELD_TYPE' => 'string',
				],
				//TYPE_ID VARCHAR (50) DEFAULT NULL,
				'TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.TYPE_ID',
					'FIELD_TYPE' => 'string',
				],
				//OPPORTUNITY DECIMAL(18,2) DEFAULT NULL,
				'OPPORTUNITY' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'D.OPPORTUNITY',
					'FIELD_TYPE' => 'double',
				],
				//IS_MANUAL_OPPORTUNITY CHAR(1) DEFAULT 'N',
				'IS_MANUAL_OPPORTUNITY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.IS_MANUAL_OPPORTUNITY',
					'FIELD_TYPE' => 'string',
				],
				//TAX_VALUE DECIMAL(18,2) DEFAULT NULL,
				'TAX_VALUE' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'D.TAX_VALUE',
					'FIELD_TYPE' => 'double',
				],
				//CURRENCY_ID VARCHAR (50) DEFAULT NULL,
				'CURRENCY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//OPPORTUNITY_ACCOUNT DECIMAL(18,2) DEFAULT NULL,
				'OPPORTUNITY_ACCOUNT' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'D.OPPORTUNITY_ACCOUNT',
					'FIELD_TYPE' => 'double',
				],
				//TAX_VALUE_ACCOUNT DECIMAL(18,2) DEFAULT NULL,
				'TAX_VALUE_ACCOUNT' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'SUM',
					'FIELD_NAME' => 'D.TAX_VALUE_ACCOUNT',
					'FIELD_TYPE' => 'double',
				],
				//ACCOUNT_CURRENCY_ID VARCHAR (50) DEFAULT NULL,
				'ACCOUNT_CURRENCY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.ACCOUNT_CURRENCY_ID',
					'FIELD_TYPE' => 'string',
				],
				//PROBABILITY TINYINT (3) DEFAULT NULL,
				'PROBABILITY' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'AVG',
					'FIELD_NAME' => 'D.PROBABILITY',
					'FIELD_TYPE' => 'int',
				],
				//COMMENTS TEXT DEFAULT NULL,
				'COMMENTS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.COMMENTS',
					'FIELD_TYPE' => 'string',
				],
				//BEGINDATE DATETIME DEFAULT NULL,
				'BEGINDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.BEGINDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//CLOSEDATE DATETIME DEFAULT NULL,
				'CLOSEDATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.CLOSEDATE',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:EVENT_DATE DATETIME NULL,
				//TODO:EVENT_ID VARCHAR(50) NULL,
				//TODO:EVENT_DESCRIPTION text NULL,
				//TODO:EXCH_RATE DECIMAL(20,4) DEFAULT 1,
				//LOCATION_ID VARCHAR(100) NULL,
				'LOCATION_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'D.LOCATION_ID',
					'FIELD_TYPE' => 'int',
				],
				//WEBFORM_ID INT (18) UNSIGNED DEFAULT NULL,
				'WEBFORM_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'D.WEBFORM_ID',
					'FIELD_TYPE' => 'int',
				],
				'WEBFORM_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WF.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'WF',
					'JOIN' => 'INNER JOIN b_crm_webform WF ON WF.ID = D.WEBFORM_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_webform WF ON WF.ID = D.WEBFORM_ID',
				],
				'WEBFORM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.WEBFORM_ID is null, null, concat_ws(\' \', concat(\'[\', D.WEBFORM_ID, \']\'), nullif(WF.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'WF',
					'JOIN' => 'INNER JOIN b_crm_webform WF ON WF.ID = D.WEBFORM_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_webform WF ON WF.ID = D.WEBFORM_ID',
				],
				//SOURCE_ID VARCHAR (50) DEFAULT NULL,
				'SOURCE_ID' => [
					'IS_METRIC' => 'Y',
					'AGGREGATION_TYPE' => 'NO_AGGREGATION',
					'FIELD_NAME' => 'D.SOURCE_ID',
					'FIELD_TYPE' => 'string',
				],
				'SOURCE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'SS.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'SS',
					'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
				],
				'SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.SOURCE_ID is null, null, concat_ws(\' \', concat(\'[\', D.SOURCE_ID, \']\'), nullif(SS.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'SS',
					'JOIN' => 'INNER JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status SS ON SS.ENTITY_ID = \'SOURCE\' and SS.STATUS_ID = D.SOURCE_ID',
				],
				//SOURCE_DESCRIPTION TEXT DEFAULT NULL,
				'SOURCE_DESCRIPTION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.SOURCE_DESCRIPTION',
					'FIELD_TYPE' => 'string',
				],
				//ORIGINATOR_ID VARCHAR(255) NULL,
				'ORIGINATOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.ORIGINATOR_ID',
					'FIELD_TYPE' => 'string',
				],
				//ORIGIN_ID VARCHAR(255) NULL,
				'ORIGIN_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.ORIGIN_ID',
					'FIELD_TYPE' => 'string',
				],
				//ADDITIONAL_INFO TEXT NULL,
				'ADDITIONAL_INFO' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.ADDITIONAL_INFO',
					'FIELD_TYPE' => 'string',
				],
				//TODO:SEARCH_CONTENT MEDIUMTEXT NULL,
				//TODO:ORDER_STAGE VARCHAR(255)DEFAULT NULL,
				//MOVED_BY_ID INT UNSIGNED DEFAULT NULL,
				'MOVED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.MOVED_BY_ID',
					'FIELD_TYPE' => 'int',
				],
				'MOVED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.MOVED_BY_ID is null, null, concat_ws(\' \', nullif(UMV.NAME, \'\'), nullif(UMV.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UMV',
					'JOIN' => 'INNER JOIN b_user UMV ON UMV.ID = D.MOVED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UMV ON UMV.ID = D.MOVED_BY_ID',
				],
				'MOVED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(D.MOVED_BY_ID is null, null, concat_ws(\' \', concat(\'[\', D.MOVED_BY_ID, \']\'), nullif(UMV.NAME, \'\'), nullif(UMV.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UMV',
					'JOIN' => 'INNER JOIN b_user UMV ON UMV.ID = D.MOVED_BY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UMV ON UMV.ID = D.MOVED_BY_ID',
				],
				//MOVED_TIME DATETIME NULL,
				'MOVED_TIME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'D.MOVED_TIME',
					'FIELD_TYPE' => 'datetime',
				],
				//LAST_ACTIVITY_BY INT UNSIGNED NOT NULL DEFAULT 0,
				//LAST_ACTIVITY_TIME DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				'UTM_SOURCE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_S.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_S',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_S.ENTITY_ID = D.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_S ON UTM_S.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_S.ENTITY_ID = D.ID and UTM_S.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_SOURCE . '\'',
				],
				'UTM_MEDIUM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_M.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_M',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_M.ENTITY_ID = D.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_M ON UTM_M.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_M.ENTITY_ID = D.ID and UTM_M.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_MEDIUM . '\'',
				],
				'UTM_CAMPAIGN' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_C.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_C',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_C.ENTITY_ID = D.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_C ON UTM_C.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_C.ENTITY_ID = D.ID and UTM_C.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CAMPAIGN . '\'',
				],
				'UTM_CONTENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_CT.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_CT',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_CT.ENTITY_ID = D.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_CT ON UTM_CT.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_CT.ENTITY_ID = D.ID and UTM_CT.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_CONTENT . '\'',
				],
				'UTM_TERM' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'UTM_T.VALUE',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UTM_T',
					'JOIN' => 'INNER JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_T.ENTITY_ID = D.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_utm UTM_T ON UTM_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND UTM_T.ENTITY_ID = D.ID and UTM_T.CODE = \'' . \Bitrix\Crm\UtmTable::ENUM_CODE_UTM_TERM . '\'',
				],
				'BANK_DETAIL_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'RL_T.BANK_DETAIL_ID',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'RL_T',
					'JOIN' => 'INNER JOIN b_crm_requisite_link RL_T ON RL_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND RL_T.ENTITY_ID = D.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_requisite_link RL_T ON RL_T.ENTITY_TYPE_ID = ' . \CCrmOwnerType::Deal . ' AND RL_T.ENTITY_ID = D.ID',
				],
			],
		];

		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['crm_deal']['DICTIONARY'] = [
				\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT,
			];
		}
		else
		{
			unset($result['crm_deal']['FIELDS']['ASSIGNED_BY_DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_deal']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_DEAL_TABLE'] ?: 'crm_deal';
		foreach ($result['crm_deal']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_DEAL_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_DEAL_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	/**
	 * Returns default deal category label.
	 *
	 * @param string $languageId Interface language identifier.
	 *
	 * @return string
	 */
	protected static function getDefaultCategoryName($languageId)
	{
		$name = \Bitrix\Main\Config\Option::get('crm', 'default_deal_category_name', '', '');
		if ($name === '')
		{
			$messages = Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/lib/category/dealcategory.php', $languageId);
			$name = $messages['CRM_DEAL_CATEGORY_DEFAULT'];
		}
		return $name;
	}
}
