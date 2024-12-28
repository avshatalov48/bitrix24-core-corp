<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\TasksMobile\Dto\DiskFileDto;
use Bitrix\TasksMobile\Dto\TaskDto;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\Provider\ChecklistProvider;
use Bitrix\TasksMobile\Provider\KanbanFieldsProvider;
use Bitrix\TasksMobile\Provider\StageProvider;
use Bitrix\TasksMobile\Provider\TaskProvider;
use Bitrix\TasksMobile\Provider\TasksMoveStageProvider;
use Bitrix\TasksMobile\Settings;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Internals\Task\Status;

class Task extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'get',
			'getUserListTasks',
			'getUserPlannerTasks',
			'getUserDeadlineTasks',
			'getProjectListTasks',
			'getProjectPlannerTasks',
			'getProjectDeadlineTasks',
			'getProjectKanbanTasks',
			'getDashboardSettings',
			'getRelatedTasksAndSubTasksByTaskId',

			'updateUserPlannerTaskStage',
			'updateUserDeadlineTaskStage',
			'updateProjectPlannerTaskStage',
			'updateProjectDeadlineTaskStage',
			'updateProjectKanbanTaskStage',
		];
	}

	/**
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getUserListTasksAction(
		TaskRequestFilter $searchParams,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation
		))->getUserListTasks();

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int|null $stageId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getUserPlannerTasksAction(
		TaskRequestFilter $searchParams,
		?int $stageId = null,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getUserPlannerTasks($stageId);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int|null $stageId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getUserDeadlineTasksAction(
		TaskRequestFilter $searchParams,
		?int $stageId = null,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getUserDeadlineTasks($stageId);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $projectId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getProjectListTasksAction(
		TaskRequestFilter $searchParams,
		int $projectId,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation
		))->getProjectListTasks($projectId);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $projectId
	 * @param int|null $stageId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getProjectPlannerTasksAction(
		TaskRequestFilter $searchParams,
		int $projectId,
		?int $stageId = null,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getProjectPlannerTasks($projectId, $stageId);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $projectId
	 * @param int|null $stageId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getProjectDeadlineTasksAction(
		TaskRequestFilter $searchParams,
		int $projectId,
		?int $stageId = null,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getProjectDeadlineTasks($projectId, $stageId);

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $projectId
	 * @param int|null $stageId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter $searchParams
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	public function getProjectKanbanTasksAction(
		TaskRequestFilter $searchParams,
		int $projectId,
		?int $stageId = null,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		PageNavigation $pageNavigation = null,
	): array
	{
		$result = (new TaskProvider(
			$this->getCurrentUser()?->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getProjectKanbanTasks($projectId, $stageId);

		return $this->convertKeysToCamelCase($result);
	}

	public function getDashboardSettingsAction(Settings $settings, int $projectId = 0, int $ownerId = 0): array
	{
		return [
			'view' => $settings->getDashboardSelectedView($projectId),
			'displayFields' => KanbanFieldsProvider::getFullState(),
			'calendarSettings' => \Bitrix\Tasks\Util\Calendar::getSettings(),
			'canCreateTask' => $this->getCanCreateTaskForDashboard($projectId, $ownerId),
		];
	}

	/**
	 * @param int $projectId
	 * @param int $ownerId
	 * @return bool
	 */
	private function getCanCreateTaskForDashboard(int $projectId = 0, int $ownerId = 0): bool
	{
		$userId = (int)$this->getCurrentUser()?->getId();
		$ownerId = ($ownerId ?: $userId);

		if ($projectId > 0 || $ownerId !== $userId)
		{
			$task = TaskModel::createFromArray([
				'CREATED_BY' => $userId,
				'RESPONSIBLE_ID' => $ownerId,
				'GROUP_ID' => $projectId,
			]);

			return TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_SAVE, null, $task);
		}

		return true;
	}

	public function getAction(int $taskId, $params = []): array|TaskDto
	{
		try
		{
			$userId = $this->getCurrentUser()?->getId();

			$provider = new TaskProvider($userId);
			$workMode = is_string($params['WORK_MODE']) && StagesTable::checkWorkMode($params['WORK_MODE'])
				? $params['WORK_MODE']
				: StagesTable::WORK_MODE_GROUP;
			$kanbanOwnerId = isset($params['KANBAN_OWNER_ID']) && (int)$params['KANBAN_OWNER_ID'] !== 0
				? (int)$params['KANBAN_OWNER_ID']
				: null;

			$getResult = $provider->getFullTask($taskId, $workMode, $kanbanOwnerId);


			if (($params['WITH_RESULT_DATA'] ?? 'Y') === 'Y')
			{
				$taskResultResult = $this->forward(new Result(), 'list', ['taskId' => $taskId]);
				$getResult = [
					...$getResult,
					'results' => $taskResultResult['results'],
					'users' => [...$getResult['users'], ...($taskResultResult['users'] ?? [])],
				];
			}

			if (($params['WITH_CHECKLIST_DATA'] ?? 'Y') === 'Y')
			{
				$checklistProvider = new ChecklistProvider($userId);
				$getResult = [
					...$getResult,
					'checklist' => [
						'CAN_ADD' => $checklistProvider->canAdd($taskId),
						'TREE' => $checklistProvider->getChecklistTree($taskId),
					],
				];
			}

			return $this->convertKeysToCamelCase($getResult);
		}
		catch (\Exception $exception)
		{
			// special case for mobile
			return [];
		}
	}

	public function addAction(array $fields): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$taskId = $provider->add($fields);

			return $this->prepareActionResult($taskId > 0, $taskId);
		}
		catch (\Exception $exception)
		{
			if ($errors = unserialize($exception->getMessage(), ['allowed_classes' => false]))
			{
				$error = $errors[0];
				$this->addError(new Error($error['text'], $error['id']));
			}
			else
			{
				$this->addError(Error::createFromThrowable($exception));
			}

			return null;
		}
	}

	public function updateAction(int $taskId, array $fields, ?string $withStageData): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$updateResult = $provider->update($taskId, $fields);

			return $this->prepareUpdateActionResult($updateResult, $taskId, $withStageData === 'Y');
		}
		catch (\Exception $exception)
		{
			if ($errors = unserialize($exception->getMessage(), ['allowed_classes' => false]))
			{
				$error = $errors[0];
				$this->addError(new Error($error['text'], $error['id']));
			}
			else
			{
				$this->addError(new Error($exception->getMessage()));
			}

			return null;
		}
	}

	public function attachUploadedFilesAction(int $taskId, string $fileId): ?DiskFileDto
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());

			return $provider->attachUploadedFiles($taskId, $fileId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function removeAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$removeResult = $provider->remove($taskId);

			return $this->prepareActionResult($removeResult);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function updateDeadlineAction(int $taskId, ?string $deadline = null): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$updateResult = $provider->update($taskId, ['DEADLINE' => ($deadline ?? '')]);

			return $this->prepareActionResult($updateResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function followAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$followResult = $provider->follow($taskId);

			return $this->prepareActionResult($followResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function unfollowAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$unfollowResult = $provider->unfollow($taskId);

			return $this->prepareActionResult($unfollowResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function startTimerAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$startTimerResult = $provider->startTimer($taskId);

			return $this->prepareActionResult($startTimerResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function pauseTimerAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$pauseTimerResult = $provider->pauseTimer($taskId);

			return $this->prepareActionResult($pauseTimerResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function startAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$startResult = $provider->start($taskId);

			return $this->prepareActionResult($startResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function takeAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$takeResult = $provider->take($taskId);

			return $this->prepareActionResult($takeResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function pauseAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$pauseResult = $provider->pause($taskId);

			return $this->prepareActionResult($pauseResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function completeAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$completeResult = $provider->complete($taskId);

			return $this->prepareCompleteActionResult($completeResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function renewAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$renewResult = $provider->renew($taskId);

			return $this->prepareRenewActionResult($renewResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function deferAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$deferResult = $provider->defer($taskId);

			return $this->prepareActionResult($deferResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function delegateAction(int $taskId, int $userId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$deferResult = $provider->delegate($taskId, $userId);

			return $this->prepareActionResult($deferResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function approveAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$approveResult = $provider->approve($taskId);

			return $this->prepareActionResult($approveResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function disapproveAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$disapproveResult = $provider->disapprove($taskId);

			return $this->prepareActionResult($disapproveResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function pingAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$pingResult = $provider->ping($taskId);

			return $this->prepareActionResult($pingResult);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function pinAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$pinResult = $provider->pin($taskId);

			return $this->prepareActionResult($pinResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function unpinAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$unpinResult = $provider->unpin($taskId);

			return $this->prepareActionResult($unpinResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function muteAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$muteResult = $provider->mute($taskId);

			return $this->prepareActionResult($muteResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function unmuteAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$unmuteResult = $provider->unmute($taskId);

			return $this->prepareActionResult($unmuteResult, $taskId);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	public function readAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$readResult = $provider->read($taskId);

			return $this->prepareActionResult($readResult);
		}
		catch (\Exception $exception)
		{
			$this->addError(Error::createFromThrowable($exception));

			return null;
		}
	}

	private function prepareActionResult(bool $isSuccess, int $taskId = null): array
	{
		$result = ['isSuccess' => $isSuccess];

		if ($taskId)
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$result['task'] = $provider->getTask($taskId);
		}

		return $result;
	}

	private function prepareCompleteActionResult(bool $isSuccess, int $taskId = null): array
	{
		$result = ['isSuccess' => $isSuccess];

		if ($taskId)
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$task = $provider->getTask($taskId);
			$result['task'] = $provider->getTask($taskId);

			$parentId = $task->parentId ?? 0;
			if ($parentId > 0)
			{
				$parentTask = $provider->getTask($parentId);
				$result['parentTask'] = $parentTask;

				if (
					$task->status === Status::COMPLETED
					&& $parentTask->status !== Status::COMPLETED
					&& (
						(
							$parentTask->isResultRequired
							&& (
								$parentTask->isOpenResultExists
								|| $parentTask->creator === $this->getCurrentUser()?->getId()
							)
						)
						|| !$parentTask->isResultRequired
					)
				)
				{
					$groupId = $task->groupId ?? 0;
					$result['areAllSubtasksCompleted'] = $provider->areAllScrumSubtasksCompleted($parentId, $groupId);
				}
			}
		}

		return $result;
	}

	private function prepareRenewActionResult(bool $isSuccess, int $taskId = null): array
	{
		$result = ['isSuccess' => $isSuccess];

		if ($taskId)
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$task = $provider->getTask($taskId);
			$result['task'] = $provider->getTask($taskId);

			$parentId = $task->parentId ?? 0;
			if ($parentId > 0)
			{
				$parentTask = $provider->getTask($parentId);
				$result['parentTask'] = $parentTask;

				if ($parentTask->status === Status::COMPLETED)
				{
					$groupId = $task->groupId ?? 0;
					$result['allSubtasksWereCompleted'] = $provider->areAllScrumSubtasksCompleted($parentId, $groupId, $taskId);
					$result['parentTask'] = $parentTask;
				}
			}
		}

		return $result;
	}

	private function prepareUpdateActionResult(bool $isSuccess, int $taskId = null, bool $withStageData = false): array
	{
		$result = ['isSuccess' => $isSuccess];

		if ($taskId)
		{
			$provider = new TaskProvider($this->getCurrentUser()?->getId());
			$result['task'] = $provider->getTask($taskId);

			$projectId = !empty($result['task']->groupId) ? (int)$result['task']->groupId : 0;
			if ($projectId !== 0 && $withStageData)
			{
				$stagesProvider = new StageProvider($this->getCurrentUser()?->getId());
				$result['stageId'] = $stagesProvider->getProjectTaskStageId($taskId, $projectId);
				$result['kanban'] =  $stagesProvider->getProjectStages($projectId, $taskId)->getData();
			}
		}

		return $result;
	}

	public function updateUserPlannerTaskStageAction(
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()?->getId(),
		))->updateUserPlannerTaskStage($id, $stageId);
	}

	public function updateProjectPlannerTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()?->getId(),
		))->updateProjectPlannerTaskStage($id, $stageId, $projectId);
	}

	public function updateUserDeadlineTaskStageAction(
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()?->getId(),
		))->updateUserDeadlineTaskStage($id, $stageId);
	}

	public function updateProjectDeadlineTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()?->getId(),
		))->updateProjectDeadlineTaskStage($id, $stageId, $projectId);
	}

	public function updateProjectKanbanTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()?->getId(),
		))->updateProjectKanbanTaskStage($id, $stageId, $projectId);
	}

	public function updateParentIdToTaskIdsAction(
		int $parentId,
		array $newSubTasks = [],
		array $deletedSubTasks = [],
	): array
	{
		return (new TaskProvider($this->getCurrentUser()?->getId()
		))->updateParentIdToTaskIds($parentId, $newSubTasks, $deletedSubTasks);
	}

	public function updateRelatedTasksAction(
		int $taskId,
		array $newRelatedTasks = [],
		array $deletedRelatedTasks = [],
	): array
	{
		return (new TaskProvider($this->getCurrentUser()?->getId()
		))->updateRelatedTasks($taskId, $newRelatedTasks, $deletedRelatedTasks);
	}
}
