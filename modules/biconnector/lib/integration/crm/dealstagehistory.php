<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class DealStageHistory
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_deal_stage_history to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_deal_stage_history table.
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

		//CREATE TABLE b_crm_deal_stage_history
		$result['crm_deal_stage_history'] = [
			'TABLE_NAME' => 'b_crm_deal_stage_history',
			'TABLE_ALIAS' => 'DSH',
			'FIELDS' => [
				//ID INT(1) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.ID',
					'FIELD_TYPE' => 'int',
				],
				//TYPE_ID INT(1) UNSIGNED NOT NULL,
				'TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				//OWNER_ID INT(1) UNSIGNED NOT NULL,
				'DEAL_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				//CREATED_TIME DATETIME NOT NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.CREATED_TIME',
					'FIELD_TYPE' => 'datetime',
				],
				//CREATED_DATE DATE NULL,
				//TODO:EFFECTIVE_DATE DATE NULL,
				//START_DATE DATE NOT NULL,
				'START_DATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.START_DATE',
					'FIELD_TYPE' => 'date',
				],
				//END_DATE DATE NOT NULL,
				'END_DATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.END_DATE',
					'FIELD_TYPE' => 'date',
				],
				//TODO:PERIOD_YEAR INT(1) UNSIGNED NOT NULL,
				//TODO:PERIOD_QUARTER INT(1) UNSIGNED NOT NULL,
				//TODO:PERIOD_MONTH INT(1) UNSIGNED NOT NULL,
				//TODO:START_PERIOD_YEAR INT(1) UNSIGNED NOT NULL,
				//TODO:START_PERIOD_QUARTER INT(1) UNSIGNED NOT NULL,
				//TODO:START_PERIOD_MONTH INT(1) UNSIGNED NOT NULL,
				//TODO:END_PERIOD_YEAR INT(1) UNSIGNED NOT NULL,
				//TODO:END_PERIOD_QUARTER INT(1) UNSIGNED NOT NULL,
				//TODO:END_PERIOD_MONTH INT(1) UNSIGNED NOT NULL,
				//RESPONSIBLE_ID INT(1) UNSIGNED NOT NULL,
				'ASSIGNED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.RESPONSIBLE_ID',
					'FIELD_TYPE' => 'int',
				],
				'ASSIGNED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(DSH.RESPONSIBLE_ID is null, null, concat_ws(\' \', nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = DSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = DSH.RESPONSIBLE_ID',
				],
				'ASSIGNED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(DSH.RESPONSIBLE_ID is null, null, concat_ws(\' \', concat(\'[\', DSH.RESPONSIBLE_ID, \']\'), nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = DSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = DSH.RESPONSIBLE_ID',
				],
				'ASSIGNED_BY_DEPARTMENT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DR.VALUE_STR',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DR',
					'JOIN' => 'INNER JOIN b_biconnector_dictionary_data DR ON DR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DR.VALUE_ID = DSH.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_biconnector_dictionary_data DR ON DR.DICTIONARY_ID = ' . \Bitrix\BIConnector\Dictionary::USER_DEPARTMENT . ' AND DR.VALUE_ID = DSH.RESPONSIBLE_ID',
				],
				//CATEGORY_ID INT (18) UNSIGNED NULL,
				'CATEGORY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.CATEGORY_ID',
					'FIELD_TYPE' => 'int',
				],
				'CATEGORY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(DSH.CATEGORY_ID is null, null, concat_ws(\' \', ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'JOIN' => 'INNER JOIN b_crm_deal_category DC ON DC.ID = DSH.CATEGORY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal_category DC ON DC.ID = DSH.CATEGORY_ID',
				],
				'CATEGORY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(DSH.CATEGORY_ID is null, null, concat_ws(\' \', concat(\'[\', DSH.CATEGORY_ID, \']\'), ifnull(DC.NAME, \'' . $helper->forSql(static::getDefaultCategoryName($languageId)) . '\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'DC',
					'JOIN' => 'INNER JOIN b_crm_deal_category DC ON DC.ID = DSH.CATEGORY_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_deal_category DC ON DC.ID = DSH.CATEGORY_ID',
				],
				//STAGE_SEMANTIC_ID VARCHAR(3) NULL,
				'STAGE_SEMANTIC_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.STAGE_SEMANTIC_ID',
					'FIELD_TYPE' => 'string',
				],
				'STAGE_SEMANTIC' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'STAGE_SEMANTIC_ID', $statusSemanticsSql),
					'FIELD_TYPE' => 'string',
				],
				//STAGE_ID VARCHAR(50) NULL,
				'STAGE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'DSH.STAGE_ID',
					'FIELD_TYPE' => 'string',
				],
				'STAGE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'S.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = DSH.STAGE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = DSH.STAGE_ID',
				],
				'STAGE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'if(DSH.STAGE_ID is null, null, concat_ws(\' \', concat(\'[\', DSH.STAGE_ID, \']\'), nullif(S.NAME, \'\')))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'S',
					'JOIN' => 'INNER JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = DSH.STAGE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_crm_status S ON S.ENTITY_ID like \'DEAL_STAGE%\' and S.STATUS_ID = DSH.STAGE_ID',
				],
				//TODO:IS_LOST CHAR(1) NULL,
			],
		];

		if (\Bitrix\BIConnector\DictionaryManager::isAvailable(\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT))
		{
			$result['crm_deal_stage_history']['DICTIONARY'] = [
				\Bitrix\BIConnector\Dictionary::USER_DEPARTMENT,
			];
		}
		else
		{
			unset($result['crm_deal_stage_history']['ASSIGNED_BY_DEPARTMENT']);
		}

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_deal_stage_history']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_DSH_TABLE'] ?: 'crm_deal_stage_history';
		foreach ($result['crm_deal_stage_history']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_DSH_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_DSH_FIELD_' . $fieldCode . '_FULL'] ?? '';
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
