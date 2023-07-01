<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

class RecycleBinOrmRepository implements RecyclebinTasksRepositoryInterface
{
	public function removeTasksFromRecycleBin(
		TasksMaxDaysInRecycleBin $maxDaysTTL,
		TasksMaxToRemoveFromRecycleBin $maxTasksToRemove
	): void
	{
		return;

		$tasksToRemove = RecyclebinTable::getList([
			'filter' => [
				'=MODULE_ID' => Manager::MODULE_ID,
				'<=TIMESTAMP' => $maxDaysTTL->getAsDateTimeFromNow(),
			],
			'limit' => $maxTasksToRemove->getValue(),
		]);
		// remove items
		foreach ($tasksToRemove as $task)
		{
			Recyclebin::remove($task['ID'], ['skipAdminRightsCheck' => true]);
		}
	}
}