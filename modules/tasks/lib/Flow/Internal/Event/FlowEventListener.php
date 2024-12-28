<?php

namespace Bitrix\Tasks\Flow\Internal\Event;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\Event\Project\FlowProjectEventHandler;
use Bitrix\Tasks\Flow\Internal\Event\Task\FlowTaskEventHandler;
use Bitrix\Tasks\Flow\Internal\Event\Template\FlowTemplateEventHandler;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionService;
use Bitrix\Tasks\Flow\Responsible;
use Exception;

class FlowEventListener
{
	/**
	 * @throws Exception
	 */
	public static function onTaskAdd(int $taskId, array $fields): void
	{
		if (!static::checkFlowId($fields))
		{
			return;
		}
		
		if ($taskId <= 0)
		{
			return;
		}
		
		(new FlowTaskEventHandler())
			->withCurrentFlowId($fields['FLOW_ID'])
			->withTaskId($taskId)
			->onTaskAdd();
	}

	/**
	 * @throws Exception
	 */
	public static function onTaskUpdate(int $taskId, array $changedFields, ?array $previousFields): void
	{
		if ($taskId <= 0)
		{
			return;
		}

		if ($previousFields === null)
		{
			return;
		}

		$previousFlowId = (int)($previousFields['FLOW_ID'] ?? null);
		$currentFlowId = (int)($changedFields['FLOW_ID'] ?? null);

		if (!array_key_exists('FLOW_ID', $changedFields) && !array_key_exists('GROUP_ID', $changedFields))
		{
			if ($previousFlowId > 0) // has flow, no changed
			{
				(new FlowTaskEventHandler())
					->withCurrentFlowId($previousFlowId)
					->withTaskId($taskId)
					->withChangedFields($changedFields)
					->withPreviousFields($previousFields)
					->onFlowTaskUpdate();
			}

			return;
		}

		if ($previousFlowId === $currentFlowId)
		{
			return;
		}

		(new FlowTaskEventHandler())
			->withPreviousFlowId($previousFlowId)
			->withCurrentFlowId($currentFlowId)
			->withTaskId($taskId)
			->withChangedFields($changedFields)
			->withPreviousFields($previousFields)
			->onTaskUpdate();
	}

	public static function onTaskDelete(int $taskId, array $params): void
	{
		if (!static::checkFlowId($params))
		{
			return;
		}

		if ($taskId <= 0)
		{
			return;
		}

		(new FlowTaskEventHandler())
			->withCurrentFlowId($params['FLOW_ID'])
			->withTaskId($taskId)
			->onTaskDelete();
	}

	/**
	 * @throws SqlQueryException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function onBeforeSocNetGroupDelete(int $groupId): bool
	{
		if ($groupId <= 0)
		{
			return true;
		}

		return (new FlowProjectEventHandler())
			->withProjectId($groupId)
			->onProjectDelete();
	}

	public static function onAfterUserUpdate($fields): void
	{
		$updatedUserId = (int)($fields['ID'] ?? null);
		$isUserFired = isset($fields['ACTIVE']) && $fields['ACTIVE'] === 'N';

		if (!$isUserFired || $updatedUserId <= 0)
		{
			return;
		}

		(new Responsible\EventHandler())->onAfterUserUpdate($updatedUserId);
	}

	public static function onAfterUserDelete($deletedUserId): void
	{
		$deletedUserId = (int)$deletedUserId;

		if ($deletedUserId <= 0)
		{
			return;
		}

		FlowUserOptionService::deleteAllForUser($deletedUserId);

		(new Responsible\EventHandler())->onAfterUserDelete($deletedUserId);
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function OnTaskTemplateDelete(int $templateId, array $fields): void
	{
		if ($templateId <= 0)
		{
			return;
		}

		(new FlowTemplateEventHandler())
			->withTemplateId($templateId)
			->onTemplateDelete();
	}

	private static function checkFlowId(array $fields): bool
	{
		return (int)($fields['FLOW_ID'] ?? 0) > 0;
	}
}
