<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Util\User;

class Task extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_STC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_STC_02';

	protected function processBeforeAction(Action $action)
	{
		if ($action->getName() === 'isParentScrumTask')
		{
			return true;
		}

		$actionArguments = $action->getArguments();

		$taskId = (is_numeric($actionArguments['taskId'] ?? null) ? (int) $actionArguments['taskId'] : 0);
		$groupId = (is_numeric($actionArguments['groupId'] ?? null) ? (int) $actionArguments['groupId'] : 0);

		$userId = User::getId();

		if ($taskId && !TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		if (!$taskId && !Group::canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_STC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * The method checks whether it is necessary to show the functionality that will decide whether
	 * to complete or continue working with the base task.
	 *
	 * @param int $taskId The parent task id.
	 * @param string $action Action on a task. 'complete' or 'renew'.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function needUpdateTaskStatusAction(int $taskId, string $action): bool
	{
		$taskId = (int) $taskId;

		$userId = User::getId();

		$action = $action === 'complete'
			? ActionDictionary::ACTION_TASK_COMPLETE
			: ActionDictionary::ACTION_TASK_RENEW
		;

		if (!TaskAccessController::can($userId, $action, $taskId))
		{
			return false;
		}

		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskId,
			],
			'select' => ['STATUS'],
		]);
		if ($taskData = $queryObject->fetch())
		{
			if (
				!(
					(
						$taskData['STATUS'] == \CTasks::STATE_COMPLETED
						&& $action === ActionDictionary::ACTION_TASK_RENEW
					)
					|| (
						$taskData['STATUS'] != \CTasks::STATE_COMPLETED
						&& $action === ActionDictionary::ACTION_TASK_COMPLETE
					)
				)
			)
			{
				return false;
			}
		}

		$isAllChildTasksCompleted = true;
		$queryObject = TaskTable::getList([
			'select' => ['ID', 'STATUS', 'PARENT_ID'],
			'filter' => [
				'PARENT_ID' => $taskId,
			],
			'order' => ['ID' => 'ASC']
		]);
		while ($childTaskData = $queryObject->fetch())
		{
			if ($childTaskData['STATUS'] != \CTasks::STATE_COMPLETED)
			{
				$isAllChildTasksCompleted = false;
			}
		}

		if ($action === ActionDictionary::ACTION_TASK_COMPLETE && $isAllChildTasksCompleted)
		{
			return true;
		}

		if ($action === ActionDictionary::ACTION_TASK_RENEW && !$isAllChildTasksCompleted)
		{
			return true;
		}

		return false;
	}

	/**
	 * The method checks if the parent task is a Scrum task in the same Scrum project.
	 *
	 * @param int $groupId Same group id as the subtask.
	 * @param int $taskId Parent task id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isParentScrumTaskAction(int $groupId, int $taskId): bool
	{
		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskId,
				'GROUP_ID' => $groupId,
			],
			'select' => ['ID'],
		]);

		return (bool) $queryObject->fetch();
	}

	/**
	 * The method returns data on tasks that are needed to be shown to the user when he makes a decision.
	 *
	 * @param int $groupId Group id.
	 * @param array $taskIds List task ids.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getTasksAction(int $groupId, array $taskIds): array
	{
		$tasks = [];

		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskIds,
				'GROUP_ID' => $groupId,
			],
			'select' => ['ID', 'TITLE'],
		]);
		while ($taskData = $queryObject->fetch())
		{
			$tasks[$taskData['ID']] = [
				'name' => $taskData['TITLE'],
			];
		}

		return $tasks;
	}

	public function completeTaskAction(int $taskId): bool
	{
		$taskId = (int) $taskId;

		$userId = User::getId();

		if (!TaskAccessController::can($userId,  ActionDictionary::ACTION_TASK_COMPLETE, $taskId))
		{
			return false;
		}

		$task = \CTaskItem::getInstance($taskId, $userId);

		$task->complete();

		return true;
	}

	public function renewTaskAction(int $taskId): bool
	{
		$taskId = (int) $taskId;

		$userId = User::getId();

		if (!TaskAccessController::can($userId,  ActionDictionary::ACTION_TASK_RENEW, $taskId))
		{
			return false;
		}

		$task = \CTaskItem::getInstance($taskId, $userId);

		$queryObject = \CTasks::getList(
			[],
			['ID' => $taskId, '=STATUS' => \CTasks::STATE_COMPLETED],
			['ID'],
			['USER_ID' => $userId]
		);
		if ($queryObject->fetch())
		{
			$task->renew();
		}

		return true;
	}

	/**
	 * The method puts the parent task into a mode in which subtasks are not visible on the sprint kanban.
	 *
	 * @param int $taskId Parent task id.
	 * @return bool
	 */
	public function proceedParentTaskAction(int $taskId): bool
	{
		$itemService = new ItemService();

		$item = $itemService->getItemBySourceId($taskId);
		if ($item->isEmpty())
		{
			return false;
		}

		$item->getInfo()->setVisibilitySubtasks('N');

		return $itemService->changeItem($item);
	}

	/**
	 * The method checks if the task is in the Scrum.
	 *
	 * @param int $taskId Task id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isScrumTaskAction(int $taskId): bool
	{
		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskId,
			],
			'select' => ['GROUP_ID'],
		]);
		if ($taskData = $queryObject->fetch())
		{
			$group = Workgroup::getById($taskData['GROUP_ID']);

			return ($group && $group->isScrumProject());
		}

		return false;
	}

	/**
	 * The method returns the missing data for the parent task status update operation.
	 *
	 * @param int $taskId Task id.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDataAction(int $taskId): array
	{
		$data = [
			'taskId' => $taskId,
		];

		$queryObject = TaskTable::getList([
			'filter' => [
				'ID' => $taskId,
			],
			'select' => ['GROUP_ID', 'PARENT_ID'],
		]);
		if ($taskData = $queryObject->fetch())
		{
			if ($taskData['GROUP_ID'])
			{
				$data['groupId'] = $taskData['GROUP_ID'];
			}

			if ($taskData['PARENT_ID'])
			{
				$data['parentTaskId'] = $taskData['PARENT_ID'];
			}
		}

		return $data;
	}
}