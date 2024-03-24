<?php

namespace Bitrix\BIConnector\Integration\Bizproc;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class WorkflowState
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

		$modulesSql = static::mapDictionarytoSqlCase(static::getModulesNames(), $helper);
		$entitiesSql = static::mapDictionarytoSqlCase(static::getEntitiesNames(), $helper);

		$result['bizproc_workflow_state'] = [
			'TABLE_NAME' => 'b_bp_workflow_state',
			'TABLE_ALIAS' => 'WS',
			'FIELDS' => [
				'ID' => [
					'IS_PRIMARY' => 'Y',
					'FIELD_NAME' => 'WS.ID',
					'FIELD_TYPE' => 'string',
				],
				'STARTED_BY_ID' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'WS.STARTED_BY',
					'FIELD_TYPE' => 'int',
				],
				'STARTED_BY_NAME' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'concat_ws(\' \', nullif(US.NAME, \'\'), nullif(US.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'US',
					'JOIN' => 'INNER JOIN b_user US ON US.ID = WS.STARTED_BY',
					'LEFT_JOIN' => 'LEFT JOIN b_user US ON US.ID = WS.STARTED_BY',
				],
				'STARTED_BY' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', US.ID, \']\'), nullif(US.NAME, \'\'), nullif(US.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'US',
					'JOIN' => 'INNER JOIN b_user US ON US.ID = WS.STARTED_BY',
					'LEFT_JOIN' => 'LEFT JOIN b_user US ON US.ID = WS.STARTED_BY',
				],
				'STARTED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'WS.STARTED',
					'FIELD_TYPE' => 'datetime',
				],
				'COMPLETED' => [
					'IS_METRIC' => 'N',
					'FIELD_NAME' => 'if(WI.ID is null, \'Y\', \'N\')',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'WI',
					'JOIN' => 'INNER JOIN b_bp_workflow_instance WI ON WI.ID = WS.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_bp_workflow_instance WI ON WI.ID = WS.ID',
				],
				'DOCUMENT_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WS.DOCUMENT_ID',
					'FIELD_TYPE' => 'string',
				],
				'DURATION' => [
					'IS_METRIC' => 'Y',
					'FIELD_NAME' => 'TIMESTAMPDIFF(SECOND, WS.STARTED, WS.MODIFIED)',
					'FIELD_TYPE' => 'int',
				],
				/* TODO: aggregate from bizpoc_task DURATION
				'TASKS_DURATION' => [
					'IS_METRIC' => 'N', // 'Y'
					//'FIELD_NAME' => 'WS.STARTED',
					'FIELD_TYPE' => 'int',
				],*/
				'START_DURATION' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WM.START_DURATION',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'WM',
					'JOIN' => 'INNER JOIN b_bp_workflow_meta WM ON WM.WORKFLOW_ID = WS.ID',
					'LEFT_JOIN' => 'LEFT JOIN b_bp_workflow_meta WM ON WM.WORKFLOW_ID = WS.ID',
				],
				'WORKFLOW_TEMPLATE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WS.WORKFLOW_TEMPLATE_ID',
					'FIELD_TYPE' => 'int',
				],
				'WORKFLOW_TEMPLATE_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WT.NAME',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'WT',
					'JOIN' => 'INNER JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
				],
				'MODULE_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WS.MODULE_ID',
					'FIELD_TYPE' => 'string',
				],
				'MODULE_ID_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'WS.MODULE_ID', $modulesSql),
					'FIELD_TYPE' => 'string',
				],
				'ENTITY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WS.ENTITY',
					'FIELD_TYPE' => 'string',
				],
				'ENTITY_ID_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => str_replace('#FIELD_NAME#', 'WS.ENTITY', $entitiesSql),
					'FIELD_TYPE' => 'string',
				],
				'MODIFIED_BY_ID' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'WT.USER_ID',
					'FIELD_TYPE' => 'int',
					'TABLE_ALIAS' => 'WT',
					'JOIN' => 'INNER JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
					'LEFT_JOIN' => 'LEFT JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
				],
				'MODIFIED_BY_NAME' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => [
						'INNER JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
						'INNER JOIN b_user UM ON UM.ID = WT.USER_ID',
					],
					'LEFT_JOIN' => [
						'LEFT JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
						'LEFT JOIN b_user UM ON UM.ID = WT.USER_ID',
					],
				],
				'MODIFIED_BY' => [
					'IS_METRIC' => 'N', // 'Y'
					'FIELD_NAME' => 'concat_ws(\' \', concat(\'[\', UM.ID, \']\'), nullif(UM.NAME, \'\'), nullif(UM.LAST_NAME, \'\'))',
					'FIELD_TYPE' => 'string',
					'TABLE_ALIAS' => 'UM',
					'JOIN' => [
						'INNER JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
						'INNER JOIN b_user UM ON UM.ID = WT.USER_ID',
					],
					'LEFT_JOIN' => [
						'LEFT JOIN b_bp_workflow_template WT ON WT.ID = WS.WORKFLOW_TEMPLATE_ID',
						'LEFT JOIN b_user UM ON UM.ID = WT.USER_ID',
					],
				],
			],
		];

		$messages = Loc::loadLanguageFile(__FILE__, $languageId);
		$result['bizproc_workflow_state']['TABLE_DESCRIPTION'] = $messages['BP_BIC_WF_STATE_TABLE'] ?: 'bizproc_workflow_state';
		foreach ($result['bizproc_workflow_state']['FIELDS'] as $fieldCode => &$fieldInfo)
		{
			$fieldInfo['FIELD_DESCRIPTION'] = $messages['BP_BIC_WF_STATE_FIELD_' . $fieldCode];
			if (!$fieldInfo['FIELD_DESCRIPTION'])
			{
				$fieldInfo['FIELD_DESCRIPTION'] = $fieldCode;
			}

			$fieldInfo['FIELD_DESCRIPTION_FULL'] = $messages['BP_BIC_WF_STATE_FIELD_' . $fieldCode . '_FULL'] ?? '';
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
			if ($value === null)
			{
				$value = $id;
			}

			$sqlId = $helper->forSql($id);
			$sqlValue = $helper->forSql($value);
			$dictionaryForSql[] = "when #FIELD_NAME# = '{$sqlId}' then '{$sqlValue}'";
		}

		return 'case ' . implode("\n", $dictionaryForSql) . ' else null end';
	}

	public static function getModulesNames(): array
	{
		return [
			'lists' => Loc::getMessage('BP_BIC_WF_STATE_MODULE_LISTS'),
			'rpa' => Loc::getMessage('BP_BIC_WF_STATE_MODULE_RPA'),
			'crm' => Loc::getMessage('BP_BIC_WF_STATE_MODULE_CRM'),
			'tasks' => Loc::getMessage('BP_BIC_WF_STATE_MODULE_TASKS'),
			'disk' => Loc::getMessage('BP_BIC_WF_STATE_MODULE_DISK'),
		];
	}

	public static function getEntitiesNames(): array
	{
		$result = [];
		$entities = [];

		if (Loader::includeModule('lists'))
		{
			$entities += [
				\BizprocDocument::class,
				\Bitrix\Lists\BizprocDocumentLists::class,
			];
		}

		if (Loader::includeModule('rpa'))
		{
			$entities[] = \Bitrix\Rpa\Integration\Bizproc\Document\Item::class;
		}

		if (Loader::includeModule('crm'))
		{
			$entities = [
				...$entities,
				\CCrmDocumentContact::class,
				\CCrmDocumentCompany::class,
				\CCrmDocumentLead::class,
				\CCrmDocumentDeal::class,
				\Bitrix\Crm\Integration\BizProc\Document\Order::class,
				\Bitrix\Crm\Integration\BizProc\Document\Invoice::class,
				\Bitrix\Crm\Integration\BizProc\Document\Shipment::class,
				\Bitrix\Crm\Integration\BizProc\Document\Quote::class,
				\Bitrix\Crm\Integration\BizProc\Document\SmartInvoice::class,
				\Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class,
				\Bitrix\Crm\Integration\BizProc\Document\SmartB2eDocument::class,
				\Bitrix\Crm\Integration\BizProc\Document\Dynamic::class,
			];
		}

		if (Loader::includeModule('tasks'))
		{
			$entities[] = \Bitrix\Tasks\Integration\Bizproc\Document\Task::class;
		}

		if (Loader::includeModule('disk'))
		{
			$entities[] = \Bitrix\Disk\BizProcDocument::class;
			$entities[] = \Bitrix\Disk\BizProcDocumentCompatible::class;
		}

		foreach ($entities as $entityClass)
		{
			if (is_callable([$entityClass, 'getEntityName']))
			{
				$result[$entityClass] = $entityClass::getEntityName($entityClass);
			}
		}

		return $result;
	}
}
