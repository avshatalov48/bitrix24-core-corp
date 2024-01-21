<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\TasksMobile\Dto\TaskDto;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\Provider\KanbanFieldsProvider;
use Bitrix\TasksMobile\Provider\TaskProvider;
use Bitrix\TasksMobile\Provider\TasksMoveStageProvider;
use Bitrix\TasksMobile\Settings;

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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
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
			$this->getCurrentUser()->getId(),
			$order,
			$extra,
			$searchParams,
			$pageNavigation,
		))->getProjectKanbanTasks($projectId, $stageId);

		return $this->convertKeysToCamelCase($result);
	}

	public function getDashboardSettingsAction(Settings $settings, int $projectId = 0): array
	{
		return [
			'view' => $settings->getDashboardSelectedView($projectId),
			'displayFields' => KanbanFieldsProvider::getFullState(),
			'calendarSettings' => \Bitrix\Tasks\Util\Calendar::getSettings(),
		];
	}

	public function getAction(int $taskId): array|TaskDto
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$getResult = $provider->getTask($taskId);

			return $this->convertKeysToCamelCase($getResult);
		}
		catch (\Exception $exception)
		{
			// special case for mobile
			return [];
		}
	}

	public function removeAction(int $taskId): ?array
	{
		try
		{
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$updateResult = $provider->update($taskId, ['DEADLINE' => ($deadline ?? '')]);

			return $this->prepareActionResult($updateResult, $taskId);
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$unfollowResult = $provider->unfollow($taskId);

			return $this->prepareActionResult($unfollowResult, $taskId);
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$completeResult = $provider->complete($taskId);

			return $this->prepareActionResult($completeResult, $taskId);
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$renewResult = $provider->renew($taskId);

			return $this->prepareActionResult($renewResult, $taskId);
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
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
			$provider = new TaskProvider($this->getCurrentUser()->getId());
			$result['task'] = $provider->getTask($taskId);
		}

		return $result;
	}

	public function updateUserPlannerTaskStageAction(
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()->getId(),
		))->updateUserPlannerTaskStage($id, $stageId);
	}

	public function updateProjectPlannerTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()->getId(),
		))->updateProjectPlannerTaskStage($id, $stageId, $projectId);
	}

	public function updateUserDeadlineTaskStageAction(
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()->getId(),
		))->updateUserDeadlineTaskStage($id, $stageId);
	}

	public function updateProjectDeadlineTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()->getId(),
		))->updateProjectDeadlineTaskStage($id, $stageId, $projectId);
	}

	public function updateProjectKanbanTaskStageAction(
		int $projectId,
		int $id,
		int $stageId,
	): bool
	{
		return (new TasksMoveStageProvider(
			$this->getCurrentUser()->getId(),
		))->updateProjectKanbanTaskStage($id, $stageId, $projectId);
	}
}
