<?php
namespace Bitrix\BIConnector\Integration\Crm;

use Bitrix\Main\Localization\Loc;

class Activity
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 * Adds a key crm_activity to the second event parameter.
	 * Fills it with data to retrieve information from b_crm_act table.
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

		$ownerTypeNames = [];
		foreach (\CCrmOwnerType::GetAllNames() as $ownerTypeName)
		{
			$ownerTypeNames[\CCrmOwnerType::ResolveID($ownerTypeName)] = $ownerTypeName;
		}
		$ownerTypesSql = static::mapDictionarytoSqlCase($ownerTypeNames, $helper);

		$directionsSql = static::mapDictionarytoSqlCase(\CCrmActivityDirection::getAllDescriptions(), $helper);
		$activityTypesSql = static::mapDictionarytoSqlCase(\CCrmActivityType::getAllDescriptions(), $helper);
		$prioritiesSql = static::mapDictionarytoSqlCase(\CCrmActivityPriority::getAllDescriptions(), $helper);
		$statusesSql = static::mapDictionarytoSqlCase(\CCrmActivityStatus::getAllDescriptions(), $helper);
		$descriptionTypesSql = static::mapDictionarytoSqlCase(\CCrmContentType::getAllDescriptions(), $helper);

		$result['crm_activity'] = [
			'TABLE_NAME' => 'b_crm_act',
			'TABLE_ALIAS' => 'A',
			'FIELDS' => [
				// ID INT(1) UNSIGNED NOT NULL AUTO_INCREMENT,
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'A.ID',
					'FIELD_TYPE' => 'int',
				],
				// TYPE_ID TINYINT(1) UNSIGNED NOT NULL,
				'TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'TYPE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.TYPE_ID', $activityTypesSql),
					'FIELD_TYPE' => 'string',
				],
				'PROVIDER_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.PROVIDER_ID',
					'FIELD_TYPE' => 'string',
				],
				'PROVIDER_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.PROVIDER_TYPE_ID',
					'FIELD_TYPE' => 'string',
				],
				//TODO:PROVIDER_GROUP_ID varchar(100) NULL,
				// OWNER_ID INT(1) NOT NULL,
				'OWNER_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.OWNER_ID',
					'FIELD_TYPE' => 'int',
				],
				// OWNER_TYPE_ID TINYINT(1) UNSIGNED NOT NULL,
				'OWNER_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.OWNER_TYPE_ID',
					'FIELD_TYPE' => 'int',
				],
				'OWNER_TYPE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.OWNER_TYPE_ID', $ownerTypesSql),
					'FIELD_TYPE' => 'string',
					//'CALLBACK' => function($value, $dateFormats)
					//{
					//	return \CCrmOwnerType::ResolveName($value);
					//}
				],
				//ASSOCIATED_ENTITY_ID INT(1),
				'ASSOCIATED_ENTITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.ASSOCIATED_ENTITY_ID',
					'FIELD_TYPE' => 'int',
				],
				//TODO:CALENDAR_EVENT_ID INT(1) UNSIGNED NOT NULL DEFAULT 0,
				// SUBJECT VARCHAR(512) NOT NULL,
				'SUBJECT' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.SUBJECT',
					'FIELD_TYPE' => 'string',
				],
				//TODO:IS_HANDLEABLE CHAR(1) NULL,
				//COMPLETED CHAR(1) NOT NULL DEFAULT 'N',
				'COMPLETED' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.COMPLETED',
					'FIELD_TYPE' => 'string',
				],
				//STATUS INT(1) UNSIGNED NOT NULL DEFAULT 0,
				'STATUS_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.STATUS',
					'FIELD_TYPE' => 'int',
				],
				'STATUS_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.STATUS', $statusesSql),
					'FIELD_TYPE' => 'string',
				],
				//RESPONSIBLE_ID INT(1) NOT NULL,
				'RESPONSIBLE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.RESPONSIBLE_ID',
					'FIELD_TYPE' => 'int',
				],
				'RESPONSIBLE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = A.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = A.RESPONSIBLE_ID',
				],
				'RESPONSIBLE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', A.RESPONSIBLE_ID, \']\'), nullif(UR.NAME, \'\'), nullif(UR.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UR',
					'JOIN' => 'INNER JOIN b_user UR ON UR.ID = A.RESPONSIBLE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UR ON UR.ID = A.RESPONSIBLE_ID',
				],
				//PRIORITY INT(1) NOT NULL,
				'PRIORITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.PRIORITY',
					'FIELD_TYPE' => 'int',
				],
				'PRIORITY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.PRIORITY', $prioritiesSql),
					'FIELD_TYPE' => 'string',
				],
				//TODO:NOTIFY_TYPE INT(1) NOT NULL,
				//TODO:NOTIFY_VALUE INT(1) UNSIGNED,
				//DESCRIPTION LONGTEXT NULL,
				'DESCRIPTION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.DESCRIPTION',
					'FIELD_TYPE' => 'string',
				],
				//DESCRIPTION_TYPE TINYINT(1) UNSIGNED NULL,
				'DESCRIPTION_TYPE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.DESCRIPTION_TYPE',
					'FIELD_TYPE' => 'int',
				],
				'DESCRIPTION_TYPE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.DESCRIPTION_TYPE', $descriptionTypesSql),
					'FIELD_TYPE' => 'string',
				],
				//DIRECTION TINYINT(1) UNSIGNED NOT NULL,
				'DIRECTION_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.DIRECTION',
					'FIELD_TYPE' => 'int',
				],
				'DIRECTION_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'A.DIRECTION', $directionsSql),
					'FIELD_TYPE' => 'string',
				],
				//TODO:LOCATION VARCHAR(256),
				//CREATED DATETIME NOT NULL,
				'DATE_CREATE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.CREATED',
					'FIELD_TYPE' => 'datetime',
				],
				//LAST_UPDATED DATETIME NOT NULL,
				'DATE_MODIFY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.LAST_UPDATED',
					'FIELD_TYPE' => 'datetime',
				],
				//START_TIME DATETIME,
				'START_TIME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.START_TIME',
					'FIELD_TYPE' => 'datetime',
				],
				//END_TIME DATETIME,
				'END_TIME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.END_TIME',
					'FIELD_TYPE' => 'datetime',
				],
				//DEADLINE DATETIME,
				'DEADLINE' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.DEADLINE',
					'FIELD_TYPE' => 'datetime',
				],
				//TODO:STORAGE_TYPE_ID TINYINT(1) UNSIGNED NULL,
				//TODO:STORAGE_ELEMENT_IDS TEXT NULL,
				//TODO:PARENT_ID INT(1) UNSIGNED NOT NULL DEFAULT 0,
				//TODO:THREAD_ID INT(1) UNSIGNED NOT NULL DEFAULT 0,
				//TODO:URN VARCHAR(64) NULL,
				//TODO:SETTINGS TEXT NULL,
				//ORIGINATOR_ID VARCHAR(255) NULL,
				'ORIGINATOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.ORIGINATOR_ID',
					'FIELD_TYPE' => 'string',
				],
				//ORIGIN_ID VARCHAR(255) NULL,
				'ORIGIN_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.ORIGIN_ID',
					'FIELD_TYPE' => 'string',
				],
				//AUTHOR_ID INT(1) UNSIGNED NULL,
				'AUTHOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.AUTHOR_ID',
					'FIELD_TYPE' => 'int',
				],
				'AUTHOR_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = A.AUTHOR_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = A.AUTHOR_ID',
				],
				'AUTHOR' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', A.AUTHOR_ID, \']\'), nullif(UA.NAME, \'\'), nullif(UA.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UA',
					'JOIN' => 'INNER JOIN b_user UA ON UA.ID = A.AUTHOR_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UA ON UA.ID = A.AUTHOR_ID',
				],
				//EDITOR_ID INT(1) UNSIGNED NULL,
				'EDITOR_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'A.EDITOR_ID',
					'FIELD_TYPE' => 'int',
				],
				'EDITOR_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UE.NAME, \'\'), nullif(UE.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UE',
					'JOIN' => 'INNER JOIN b_user UE ON UE.ID = A.EDITOR_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UE ON UE.ID = A.EDITOR_ID',
				],
				'EDITOR' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', A.EDITOR_ID, \']\'), nullif(UE.NAME, \'\'), nullif(UE.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UE',
					'JOIN' => 'INNER JOIN b_user UE ON UE.ID = A.EDITOR_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_user UE ON UE.ID = A.EDITOR_ID',
				],
				//TODO:PROVIDER_PARAMS LONGTEXT NULL,
				//TODO:PROVIDER_DATA LONGTEXT NULL,
				//TODO:SEARCH_CONTENT MEDIUMTEXT NULL,
				//TODO:RESULT_STATUS INT(1) NOT NULL DEFAULT 0,
				//TODO:RESULT_STREAM INT(1) NOT NULL DEFAULT 0,
				//TODO:RESULT_SOURCE_ID VARCHAR(255) NULL,
				//TODO:RESULT_MARK INT(1) NOT NULL DEFAULT 0,
				//TODO:RESULT_VALUE DECIMAL(18,4) DEFAULT NULL,
				//TODO:RESULT_SUM DECIMAL(18,4) DEFAULT NULL,
				//TODO:RESULT_CURRENCY_ID CHAR(3) NULL,
				//TODO:AUTOCOMPLETE_RULE INT(1) UNSIGNED NOT NULL DEFAULT 0,
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['crm_activity']['TABLE_DESCRIPTION'] = $messages['CRM_BIC_ACTIVITY_TABLE'] ?: 'crm_activity';
		foreach ($result['crm_activity']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['CRM_BIC_ACTIVITY_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['CRM_BIC_ACTIVITY_FIELD_' . $fieldCode . '_FULL'] ?? '';
		}
		unset($fieldInfo);
	}

	/**
	 * Transforms php array into sql CASE operator.
	 *
	 * @param array $dictionary Items array to transform.
	 * @param \Bitrix\Main\DB\SqlHelper $helper Sql helper to escape strings.
	 *
	 * @return string
	 */
	public static function mapDictionarytoSqlCase($dictionary, $helper)
	{
		krsort($dictionary);

		$dictionaryForSql = [];
		foreach ($dictionary as $id => $value)
		{
			if ($id)
			{
				array_unshift(
					$dictionaryForSql,
					'when #FIELD_NAME# = \'' . $helper->forSql($id) . '\' then \'' . $helper->forSql($value) . '\''
				);
			}
			else
			{
				$dictionaryForSql[] = 'when #FIELD_NAME# is null or #FIELD_NAME# = \'\' then \'' . $helper->forSql($value) . '\'';
			}
		}
		$dictionarySql = 'case ' . implode("\n", $dictionaryForSql) . ' else null end';

		return $dictionarySql;
	}
}
