<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Compatibility;

use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;
use CTasks;

class TaskRepository
{
	/**
	 * @throws TaskNotExistsException
	 */
	public function getTaskData(int $id): array
	{
		$data = CTasks::GetByID($id, false)->Fetch();
		if (empty($data))
		{
			throw new TaskNotExistsException();
		}

		$data['ID'] = (int)$data['ID'];


		return $data;
	}

	/**
	 * @throws TaskNotExistsException
	 */
	public function getTaskObject(int $id): TaskObject
	{
		$task = TaskRegistry::getInstance()->getObject($id, true)?->fillAllMemberIds();
		if ($task === null)
		{
			throw new TaskNotExistsException();
		}

		return $task;
	}
}