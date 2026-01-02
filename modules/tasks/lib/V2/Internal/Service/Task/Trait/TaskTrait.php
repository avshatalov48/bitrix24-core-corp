<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use CTasks;

trait TaskTrait
{
	/**
	 * @throws TaskNotExistsException
	 */
	private function getFullTaskData(int $taskId): array
	{
		$data = CTasks::GetByID($taskId, false)->Fetch();
		if (empty($data))
		{
			throw new TaskNotExistsException();
		}

		$data['ID'] = (int)$data['ID'];


		return $data;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws TaskNotFoundException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getTaskObject(int $taskId): TaskObject
	{
		// todo
		$memberList = MemberTable::getList([
			'select' => [
				'*',
			],
			'filter' => [
				'=TASK_ID' => $taskId,
			],
		])->fetchCollection();

		$select = ['*', 'UTS_DATA', 'FLOW_TASK'];
		if (!$memberList->isEmpty())
		{
			$select[] = 'MEMBER_LIST';
		}

		$task = TaskTable::getByPrimary($taskId, ['select' => $select])->fetchObject()?->cacheCrmFields();

		if ($task === null)
		{
			throw new TaskNotExistsException();
		}

		$task->fillMemberList();
		$task->fillScenario();

		return $task;
	}
}
