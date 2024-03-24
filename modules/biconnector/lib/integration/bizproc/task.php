<?php

namespace Bitrix\BIConnector\Integration\Bizproc;

use Bitrix\Main\Localization\Loc;

class Task
{
	/**
	 * Event handler for onBIConnectorDataSources event.
	 *
	 * @param \Bitrix\Main\Event $event Event data.
	 *
	 * @return void
	 */
	public static function onBIConnectorDataSources(\Bitrix\Main\Event $event)
	{
		if (!\Bitrix\Main\Loader::includeModule('bizproc'))
		{
			return;
		}

		$params = $event->getParameters();
		$manager = $params[0];
		$result = &$params[1];
		$languageId = $params[2];

		$connection = $manager->getDatabaseConnection();
		$helper = $connection->getSqlHelper();

		$taskStatusesSql = static::mapDictionarytoSqlCase(static::getTaskStatuses(), $helper);
		$taskIdsStatusesSql = static::mapDictionarytoSqlCase(static::getTaskIdsStatuses(), $helper);
		$taskCodesSql = static::mapDictionarytoSqlCase(static::getTaskCodesNames(), $helper);
		$taskIdsCodesSql = static::mapDictionarytoSqlCase(static::getTaskCodesIdsNames(), $helper);
		$taskTypeIdsSql = static::mapSerializedDictionarytoSqlCase(static::getTaskApproveTypeIds(), $helper);
		$taskTypeNamesSql = static::mapSerializedDictionarytoSqlCase(static::getTaskApproveTypeNames(), $helper);

		$result['bizproc_task'] = [
			'TABLE_NAME' => 'b_bp_task',
			'TABLE_ALIAS' => 'T',
			'FIELDS' => [
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'T.ID',
					'FIELD_TYPE' => 'int',
				],
				'CODE_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.ACTIVITY',
					'FIELD_TYPE' => 'string',
				],
				'CODE_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.ACTIVITY', $taskCodesSql),
					'FIELD_TYPE' => 'string',
				],
				'CODE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.ACTIVITY', $taskIdsCodesSql),
					'FIELD_TYPE' => 'string',
				],
				'WORKFLOW_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.WORKFLOW_ID',
					'FIELD_TYPE' => 'string',
				],
				'NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.NAME',
					'FIELD_TYPE' => 'string',
				],
				'CREATED_DATE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.CREATED_DATE',
					'FIELD_TYPE' => 'datetime',
				],
				'MODIFIED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.MODIFIED',
					'FIELD_TYPE' => 'datetime',
				],
				'DURATION' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'TIMESTAMPDIFF(SECOND, T.CREATED_DATE, T.MODIFIED)',
					'FIELD_TYPE' => 'int',
				],
				'APPROVE_TYPE' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.PARAMETERS', $taskTypeIdsSql),
					'FIELD_TYPE' => 'string',
				],
				'APPROVE_TYPE_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.PARAMETERS', $taskTypeNamesSql),
					'FIELD_TYPE' => 'string',
				],
				'STATUS_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'T.STATUS',
					'FIELD_TYPE' => 'int',
				],
				'STATUS_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.STATUS', $taskStatusesSql),
					'FIELD_TYPE' => 'string',
				],
				'STATUS' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'T.STATUS', $taskIdsStatusesSql),
					'FIELD_TYPE' => 'string',
				],
				'USER_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'TU.USER_ID',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'TU',
					'JOIN' => 'INNER JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
				],
				'USER_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'concat_ws(\' \', nullif(U.NAME, \'\'), nullif(U.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'U',
					'JOIN' => [
						'INNER JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
						'INNER JOIN b_user U ON U.ID = TU.USER_ID',
					],
					'LEFT_JOIN' => [
						'LEFT JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
						'LEFT JOIN b_user U ON U.ID = TU.USER_ID',
					],
				],
				'USER' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', U.ID, \']\'), nullif(U.NAME, \'\'), nullif(U.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'U',
					'JOIN' => [
						'INNER JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
						'INNER JOIN b_user U ON U.ID = TU.USER_ID',
					],
					'LEFT_JOIN' => [
						'LEFT JOIN b_bp_task_user TU ON TU.TASK_ID = T.ID',
						'LEFT JOIN b_user U ON U.ID = TU.USER_ID',
					],
				],
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['bizproc_task']['TABLE_DESCRIPTION'] = $messages['BP_BIC_TASK_TABLE'] ?: 'bizproc_task';
		foreach ($result['bizproc_task']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['BP_BIC_TASK_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['BP_BIC_TASK_FIELD_' . $fieldCode . '_FULL'] ?? '';
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
		ksort($dictionary);
		$dictionaryForSql = [];
		foreach ($dictionary as $id => $value)
		{
			$sqlId = $helper->forSql($id);
			$sqlValue = $helper->forSql($value);
			$dictionaryForSql[] = "when #FIELD_NAME# = '{$sqlId}' then '{$sqlValue}'";
		}

		return 'case ' . implode("\n", $dictionaryForSql) . ' else null end';
	}

	/**
	 * Transforms php array into sql CASE operator.
	 *
	 * @param array $dictionary Items array to transform.
	 * @param \Bitrix\Main\DB\SqlHelper $helper Sql helper to escape strings.
	 *
	 * @return string
	 */
	public static function mapSerializedDictionarytoSqlCase($dictionary, $helper)
	{
		ksort($dictionary);

		$default = 'null';
		$dictionaryForSql = [];
		foreach ($dictionary as $id => $value)
		{
			if ($id === '')
			{
				$default = "'" . $helper->forSql($value) . "'";
				continue;
			}

			$sqlId = $helper->forSql($id);
			$sqlValue = $helper->forSql($value);
			$dictionaryForSql[] = "when LOCATE('{$sqlId}', #FIELD_NAME#) > 0 then '{$sqlValue}'";
		}

		return 'case ' . implode("\n", $dictionaryForSql) . ' else ' . $default . ' end';
	}

	public static function getTaskStatuses(): array
	{
		return [
			0 => Loc::getMessage('BP_BIC_TASK_STATUS_0'),
			1 => Loc::getMessage('BP_BIC_TASK_STATUS_1'),
			2 => Loc::getMessage('BP_BIC_TASK_STATUS_2'),
			3 => Loc::getMessage('BP_BIC_TASK_STATUS_3'),
			4 => Loc::getMessage('BP_BIC_TASK_STATUS_4'),
			5 => Loc::getMessage('BP_BIC_TASK_STATUS_5'),
		];
	}

	public static function getTaskIdsStatuses()
	{
		$names = static::getTaskStatuses();

		foreach ($names as $id => &$name)
		{
			$name = "[{$id}] {$name}";
		}

		return $names;
	}

	public static function getTaskCodesNames(): array
	{
		return [
			'ApproveActivity' => Loc::getMessage('BP_BIC_TASK_CODE_APPROVE'),
			'ReviewActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REVIEW'),
			'RequestInformationActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REQUEST'),
			'RequestInformationOptionalActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REQUEST_OPTIONAL'),
			'RpaApproveActivity' => Loc::getMessage('BP_BIC_TASK_CODE_APPROVE'),
			'RpaReviewActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REVIEW'),
			'RpaRequestActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REQUEST'),
			'RpaMoveActivity' => Loc::getMessage('BP_BIC_TASK_CODE_REQUEST'),
		];
	}

	public static function getTaskCodesIdsNames()
	{
		$names = static::getTaskCodesNames();

		foreach ($names as $code => &$name)
		{
			$name = "[{$code}] {$name}";
		}

		return $names;
	}

	public static function getTaskApproveTypeIds(): array
	{
		return [
			'' => 'any',
			'"ApproveType";s:3:"all"' => 'all',
			'"ApproveType";s:4:"vote"' => 'vote',
			'"ApproveType";s:5:"queue"' => 'queue',
		];
	}

	public static function getTaskApproveTypeNames(): array
	{
		return [
			'' => Loc::getMessage('BP_BIC_TASK_TYPE_ANY'),
			'"ApproveType";s:3:"all"' => Loc::getMessage('BP_BIC_TASK_TYPE_ALL'),
			'"ApproveType";s:4:"vote"' => Loc::getMessage('BP_BIC_TASK_TYPE_VOTE'),
			'"ApproveType";s:5:"queue"' => Loc::getMessage('BP_BIC_TASK_TYPE_QUEUE'),
		];
	}
}
