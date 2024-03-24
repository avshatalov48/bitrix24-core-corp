<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class LeadStatusHistory
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_lead_status_history to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_lead_status_history table.
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

		//CREATE TABLE b_crm_lead_status_history
		$result['crm_lead_status_history'] = [
			'TABLE_NAME' => 'b_crm_lead_status_history',
			'TABLE_ALIAS' => 'LSH',
			'FIELDS' => [
				//ID INT(1) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.ID',
					'FIELD_TYPE' => 'int',
				],
				//TYPE_ID INT(1) UNSIGNED NOT NULL,
				'TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				//OWNER_ID INT(1) UNSIGNED NOT NULL,
				'LEAD_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				//CREATED_TIME DATETIME NOT NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.CREATED_TIME',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:CREATED_DATE DATE NULL,
				//TODO:PERIOD_YEAR INT(1) UNSIGNED NOT NULL,
				//TODO:PERIOD_QUARTER INT(1) UNSIGNED NOT NULL,
				//TODO:PERIOD_MONTH INT(1) UNSIGNED NOT NULL,
				//RESPONSIBLE_ID INT(1) UNSIGNED NOT NULL,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.RESPONSIBLE_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(LSH.RESPONSIBLE_ID is null, null, concat_ws(\' \', nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = LSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = LSH.RESPONSIBLE_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(LSH.RESPONSIBLE_ID is null, null, concat_ws(\' \', concat(\'[\', LSH.RESPONSIBLE_ID, \']\'), nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = LSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = LSH.RESPONSIBLE_ID',
				],
				'ASSIGNED_BY_DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DR.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DR',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data DR ON DR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DR.VALUE_ID = LSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data DR ON DR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DR.VALUE_ID = LSH.RESPONSIBLE_ID',
				],
				//STATUS_SEMANTIC_ID VARCHAR(3) NULL,
				'STATUS_SEMANTIC_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.STATUS_SEMANTIC_ID',
					'FIELD_TYPE' => 'string',
				],
				'STATUS_SEMANTIC' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'STATUS_SEMANTIC_ID', $statusSemanticsSql),
					'FIELD_TYPE' => 'string',
				],
				//STATUS_ID VARCHAR(50) NULL,
				'STATUS_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'LSH.STATUS_ID',
					'FIELD_TYPE' => 'string',
				],
				'STATUS_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = LSH.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = LSH.STATUS_ID',
				],
				'STATUS' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(LSH.STATUS_ID is null, null, concat_ws(\' \', concat(\'[\', LSH.STATUS_ID, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = LSH.STATUS_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID = \'STATUS\' and S.STATUS_ID = LSH.STATUS_ID',
				],
				//TODO:IS_IN_WORK CHAR(1) NULL,
				//TODO:IS_JUNK CHAR(1) NULL,
			],
		];

		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['crm_lead_status_history']['DICTIONARY'] = [
				\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT,
			];
		}
		else
		{
			unset($result['crm_lead_status_history']['ASSIGNED_BY_DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_lead_status_history']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_LSH_TABLE'] ?: 'crm_lead_status_history';
		foreach ($result['crm_lead_status_history']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_LSH_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_LSH_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}
}
