<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use \Bitrix\Tasks\Integration\Pull\PushService;
use \Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Internals\Task\Status;

final class TasksMoveStageProvider
{
	private int $userId;

	function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function updateUserPlannerTaskStage(int $taskId, int $stageId): bool
	{
		return $this->updateTaskStage($taskId, $stageId, StagesTable::WORK_MODE_USER);
	}

	public function updateProjectPlannerTaskStage(int $taskId, int $stageId, int $projectId): bool
	{
		return $this->updateTaskStage($taskId, $stageId, StagesTable::WORK_MODE_USER, $projectId);
	}

	public function updateUserDeadlineTaskStage(int $taskId, int $stageId): bool
	{
		return $this->updateTaskStage($taskId, $stageId, StagesTable::WORK_MODE_TIMELINE);
	}

	public function updateProjectDeadlineTaskStage(int $taskId, int $stageId, int $projectId): bool
	{
		return $this->updateTaskStage($taskId, $stageId, StagesTable::WORK_MODE_TIMELINE, $projectId);
	}

	public function updateProjectKanbanTaskStage(int $taskId, int $stageId, int $projectId): bool
	{
		return $this->updateTaskStage($taskId, $stageId, StagesTable::WORK_MODE_GROUP, $projectId);
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	private function updateTaskStage(
		int $taskId,
		int$stageId,
		?string $workMode,
		?int $projectId = null
	): bool
	{
		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		$entityId = $workMode === StagesTable::WORK_MODE_GROUP ? $projectId : $this->userId;

		$group = WorkGroup::getById($entityId);
		$isScrumTask = $group && $group->isScrumProject();
		if ($isScrumTask)
		{
			$kanbanService = new KanbanService();
			$stages = $kanbanService->getStagesToTask($taskId);
		}
		else
		{
			$prevWorkMode = StagesTable::getWorkMode();
			StagesTable::setWorkMode($workMode);
			$stages = StagesTable::getStages($entityId);
			StagesTable::setWorkMode($prevWorkMode);
		}

		if (!isset($stages[$stageId]))
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		$stage = $stages[$stageId];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		$task = TaskRegistry::getInstance()->get($taskId);
		if (!$task)
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		if ($isScrumTask)
		{
			$featurePerms = \CSocNetFeaturesPerms::currentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$task['GROUP_ID']],
				'tasks',
				'sort'
			);
			$isAccess = (
				is_array($featurePerms)
				&& isset($featurePerms[$task['GROUP_ID']])
				&& $featurePerms[$task['GROUP_ID']]
			);
			if (!$isAccess)
			{
				return false;
			}

			$kanbanService = new KanbanService();

			$result = $kanbanService->moveTask($taskId, $stage['ID']);
			if ($stage['SYSTEM_TYPE'] === StagesTable::SYS_TYPE_FINISH)
			{
				$task = \CTaskItem::getInstance($taskId, User::getId());
				if (
					$task->checkAccess(ActionDictionary::ACTION_TASK_COMPLETE)
					|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
				)
				{
					$task->complete();
				}
			}
			else
			{
				$task = \CTaskItem::getInstance($taskId, User::getId());
				if (
					$task->checkAccess(ActionDictionary::ACTION_TASK_RENEW)
					|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
				)
				{
					$queryObject = \CTasks::getList(
						[],
						['ID' => $taskId, '=STATUS' => Status::COMPLETED],
						['ID'],
						['USER_ID' => User::getId()]
					);
					if ($queryObject->fetch())
					{
						$task->renew();
					}
				}
			}

			return $result;
		}

		if (
			$stage['ENTITY_TYPE'] === StagesTable::WORK_MODE_GROUP
			&& !SocialNetwork\Group::can($stage['ENTITY_ID'], SocialNetwork\Group::ACTION_SORT_TASKS)
		)
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		if (
			$stage['ENTITY_TYPE'] !== StagesTable::WORK_MODE_GROUP
			&& ((int)$stage['ENTITY_ID']) !== $this->userId
		)
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		// check if new and old stages in different Kanbans
		if (
			$stage['ENTITY_TYPE'] === StagesTable::WORK_MODE_GROUP
			&& $task['GROUP_ID'] !== $stage['ENTITY_ID']
		)
		{
			throw new ArgumentException(Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
		}

		$taskObject = new \CTasks;
		SortingTable::deleteByTaskId($stageId);
		if (isset($stage['TO_UPDATE']))
		{
			$accessAllowed = true;
			if ($stage['TO_UPDATE_ACCESS'])
			{
				$taskInst = \CTaskItem::getInstance($taskId, $this->userId);
				if (!$taskInst->checkAccess(ActionDictionary::getActionByLegacyId($stage['TO_UPDATE_ACCESS'])))
				{
					$accessAllowed = false;
				}
			}
			if ($accessAllowed)
			{
				$taskObject->update(
					$task['ID'],
					$stage['TO_UPDATE']
				);
			}
		}

		if ($workMode === StagesTable::WORK_MODE_TIMELINE)
		{
			return true;
		}

		if ($workMode === StagesTable::WORK_MODE_USER)
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);

			$resStg = TaskStageTable::getList([
				'filter' => [
					'TASK_ID' => $task['ID'],
					'=STAGE.ENTITY_TYPE' => StagesTable::getWorkMode(),
					'STAGE.ENTITY_ID' => $entityId,
				],
			]);
			while ($rowStg = $resStg->fetch())
			{
				TaskStageTable::update($rowStg['ID'], [
					'STAGE_ID' => $stageId,
				]);

				Listener::onPlanTaskStageUpdate(
					$entityId,
					$rowStg['TASK_ID'],
					$stageId,
				);
			}

			StagesTable::setWorkMode($prevWorkMode);
		}
		else
		{
			$taskObject->update($task['ID'], [
				'STAGE_ID' => $stageId,
			]);
			PushService::addEvent($this->getMembersTask($task, $projectId), [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_STAGE_UPDATED,
				'params' => [
					'taskId' => $task['ID'],
				],
			]);
		}

		return true;
	}

	private function getMembersTask($taskData, $projectId): array
	{
		$members = [];

		$members[] = $taskData['CREATED_BY'] ?? null;
		$members[] = $taskData['RESPONSIBLE_ID'] ?? null;

		$res = \CTaskMembers::getList(
			[],
			['TASK_ID' => $taskData['id']]
		);
		while ($row = $res->fetch())
		{
			$members[] = $row['USER_ID'];
		}

		if ($projectId)
		{
			$groupMembers = SocialNetwork\User::getUsersCanPerformOperation(
				$projectId,
				'view_all'
			);
			$members = array_unique(array_merge($members, $groupMembers));
		}

		return $members;
	}
}
