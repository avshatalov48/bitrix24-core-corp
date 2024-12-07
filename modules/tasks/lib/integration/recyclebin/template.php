<?php

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\Internals\Task\TemplateTable;

use Bitrix\Recyclebin\Internals\Entity;
use Bitrix\Recyclebin\Internals\Contracts\Recyclebinable;
use Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use CTaskTemplates;
use Exception;

/**
 * Class Template
 *
 * @package Bitrix\Tasks\Integration\Recyclebin
 */

if (!Loader::includeModule('recyclebin'))
{
	return;
}

class Template implements Recyclebinable
{
	public static function OnBeforeDelete($templateId, array $template = []): Result
	{
		$recyclebin = new Entity($templateId, Manager::TASKS_TEMPLATE_RECYCLEBIN_ENTITY, Manager::MODULE_ID);
		$recyclebin->setTitle($template['TITLE']);
		$result = $recyclebin->save();

		return $result;
	}

	public static function moveFromRecyclebin(Entity $entity): Result
	{
		$result = new Result();

		$templateId = $entity->getEntityId();
		$connection = Application::getConnection();

		try
		{
			$connection->queryExecute('UPDATE '
				. TemplateTable::getTableName()
				. ' SET ZOMBIE = \'N\' WHERE ID = '
				. $templateId);
		}
		catch (Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		try
		{
			$select = ['ID', 'CREATED_BY', 'REPLICATE', 'REPLICATE_PARAMS', 'TPARAM_REPLICATION_COUNT'];
			$template = CTaskTemplates::getList([], ['ID' => $templateId], [], [], $select)->fetch();

			if ($template && $template["REPLICATE"] == "Y")
			{
				$replicator = new RegularTemplateTaskReplicator();
				$replicator->startReplication($templateId);
			}
		}
		catch (Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return $result;
	}

	/**
	 * Removes entity from recycle bin
	 */
	public static function removeFromRecyclebin(Entity $entity, array $params = []): Result
	{
		$result = new Result;

		try
		{
			$templateId = $entity->getEntityId();
			$params = ['UNSAFE_DELETE_ONLY' => true];

			CTaskTemplates::Delete($templateId, $params);
		}
		catch (Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return $result;
	}

	public static function getNotifyMessages(): array
	{
		return [
			'NOTIFY' => [
				'RESTORE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_RESTORE_MESSAGE'),
				'REMOVE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_REMOVE_MESSAGE'),
			],
			'CONFIRM' => [
				'RESTORE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_RESTORE_CONFIRM'),
				'REMOVE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_REMOVE_CONFIRM'),
			],
		];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getAdditionalData(): array
	{
		return [
			'LIMIT_DATA' => [
				'RESTORE' => [
					'DISABLE' => !Bitrix24::checkFeatureEnabled(FeatureDictionary::TASK_RECYCLE_BIN_RESTORE),
					'FEATURE_ID' => FeatureDictionary::TASK_RECYCLE_BIN_RESTORE,
					'SLIDER_CODE' => 'limit_tasks_recycle_bin_restore',
				],
			],
		];
	}

	public static function getDeleteMessage(int $userId = 0): string
	{
		return Loc::getMessage('TASKS_RECYCLEBIN_TEMPLATE_MOVED_TO_RECYCLEBIN', [
			'#RECYCLEBIN_URL#' => str_replace('#user_id#', $userId, RouteDictionary::PATH_TO_RECYCLEBIN),
		]);
	}

	/**
	 * @deprecated and will be removed since recyclebin 23.0.0
	 * @throws NotImplementedException
	 */
	public static function previewFromRecyclebin(Entity $entity): void
	{
		throw new NotImplementedException("Coming soon...");
	}
}