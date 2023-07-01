<?php

namespace Bitrix\Crm\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\EO_CheckList;

class TaskChecklist
{
	public static function getNotRootChecklistItems(int $taskId): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		$checklistItems = CheckListTable::getAllByTaskId($taskId)->getAll();
		return array_filter($checklistItems,
			static fn (EO_CheckList $checklist): bool => $checklist->getTreeByChild()->getLevel() !== 0
		);
	}

	public static function getCompletedChecklistItemsCount(int $taskId): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return array_filter(static::getNotRootChecklistItems($taskId),
			static fn (EO_CheckList $checklist): bool => $checklist->getIsComplete()
		);
	}
}