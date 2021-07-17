<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Tasks\Integration\Recyclebin\Task as TaskRecycleBin;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\ProjectsTable;
use Bitrix\Tasks\Scrum\Internal\EntityTable;

class KanbanService implements Errorable
{
	const ERROR_COULD_NOT_ADD_TASK = 'TASKS_KS_01';
	const ERROR_COULD_NOT_REMOVE_TASK = 'TASKS_KS_02';
	const ERROR_COULD_NOT_GET_TASKS = 'TASKS_KS_03';
	const ERROR_COULD_NOT_GET_STAGES = 'TASKS_KS_04';
	const ERROR_COULD_NOT_ADD_ONE_TASK = 'TASKS_KS_05';
	const ERROR_COULD_NOT_GET_FINISH_STAGE = 'TASKS_KS_06';
	const ERROR_COULD_NOT_CHECK_IS_TASK_IN_BASKET = 'TASKS_TS_07';

	private $errorCollection;

	private $unFinishedTaskIdsCache = [];
	private $finishedTaskIdsCache = [];

	private static $sprintStageIds = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * The method returns the stages for the task, depending on whether it is in the active sprint,
	 * completed sprint, or in the backlog.
	 *
	 * @param int $taskId Task id.
	 * @return array
	 */
	public function getStagesToTask(int $taskId): array
	{
		$itemService = new ItemService();
		$entityService = new EntityService();

		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return [];
		}

		$entity = $entityService->getEntityById($scrumItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return [];
		}

		if ($entity->getEntityType() === EntityTable::BACKLOG_TYPE)
		{
			return [];
		}

		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		return StagesTable::getStages($entity->getId(), true);
	}

	/**
	 * The method returns the stage id for the task.
	 *
	 * @param int $taskId Task id.
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getTaskStageId(int $taskId): int
	{
		$itemService = new ItemService();
		$entityService = new EntityService();

		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return 0;
		}

		$entity = $entityService->getEntityById($scrumItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return 0;
		}

		if ($entity->getEntityType() === EntityTable::BACKLOG_TYPE)
		{
			return 0;
		}

		$queryObject = TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $taskId,
				'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'STAGE.ENTITY_ID' => $entity->getId()
			]
		]);
		if ($taskStage = $queryObject->fetch())
		{
			return $taskStage['STAGE_ID'];
		}

		return 0;
	}

	/**
	 * The method returns the entity id for the task.
	 *
	 * @param int $taskId Task id.
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getTaskEntityId(int $taskId): int
	{
		$itemService = new ItemService();
		$entityService = new EntityService();

		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return 0;
		}

		$entity = $entityService->getEntityById($scrumItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return 0;
		}

		return $entity->getId();
	}

	/**
	 * Add the tasks to default stage of the sprint.
	 *
	 * @param int $sprintId Sprint id.
	 * @param array $taskIds List task id.
	 * @return bool
	 */
	public function addTasksToKanban(int $sprintId, array $taskIds): bool
	{
		try
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$defaultStageId = StagesTable::getDefaultStageId($sprintId);

			if (!$defaultStageId)
			{
				$this->errorCollection->setError(
					new Error('Failed to get the default stage', self::ERROR_COULD_NOT_ADD_TASK)
				);
				return false;
			}

			$this->removeTasksFromKanban($sprintId, $taskIds);

			foreach ($taskIds as $taskId)
			{
				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => $defaultStageId,
				]);
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_TASK));
			return false;
		}
	}

	/**
	 * Add the sub tasks to stage of the sprint. Saves positions for sub tasks that were in the previous sprint.
	 *
	 * @param int $sprintId Sprint id.
	 * @param array $taskIds List task id.
	 * @return bool
	 */
	public function addSubTasksToKanban(int $sprintId, array $taskIds): bool
	{
		try
		{
			if (empty($taskIds))
			{
				return false;
			}

			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$defaultStageId = StagesTable::getDefaultStageId($sprintId);

			$taskStageIdsMap = [];
			if ($lastSprintId = $this->getLastCompletedSprintIdSameGroup($sprintId))
			{
				$stageIdsMap = $this->getStageIdsMapBetweenTwoSprints($sprintId, $lastSprintId);

				$lastStages = $this->getStagesCompletedSprint($lastSprintId);
				foreach ($lastStages as $lastStage)
				{
					$taskIdsInLastSprint = $this->getTaskIds([
						'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
						'TASK_ID' => $taskIds,
						'STAGE_ID' => $lastStage['ID']
					]);
					if ($taskIdsInLastSprint)
					{
						foreach ($taskIdsInLastSprint as $taskIdInLastSprint)
						{
							$taskStageIdsMap[$taskIdInLastSprint] = $stageIdsMap[$lastStage['ID']];
						}
					}
				}
			}

			if (!$defaultStageId)
			{
				$this->errorCollection->setError(
					new Error('Failed to get the default stage', self::ERROR_COULD_NOT_ADD_TASK)
				);
				return false;
			}

			$this->removeTasksFromKanban($sprintId, $taskIds);

			foreach ($taskIds as $taskId)
			{
				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => (isset($taskStageIdsMap[$taskId]) ? $taskStageIdsMap[$taskId] : $defaultStageId),
				]);
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_TASK));
			return false;
		}
	}

	public function getNewStatus(): string
	{
		return StagesTable::SYS_TYPE_NEW;
	}

	public function getFinishStatus(): string
	{
		return StagesTable::SYS_TYPE_FINISH;
	}

	public function addTaskToFinishStatus(int $sprintId, int $taskId): void
	{
		try
		{
			$finishStageId = $this->getFinishStageId($sprintId);

			if ($finishStageId)
			{
				$this->removeTasksFromKanban($sprintId, [$taskId]);

				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => $finishStageId
				]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK));
		}
	}

	//todo static cache
	public function isTaskInFinishStatus(int $sprintId, int $taskId): bool
	{
		try
		{
			$finishStageId = $this->getFinishStageId($sprintId);

			if ($finishStageId)
			{
				$queryObject = TaskStageTable::getList([
					'filter' => [
						'TASK_ID' => $taskId,
						'STAGE_ID' => $finishStageId
					]
				]);
				return ($queryObject->fetch() ? true : false);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK));
		}

		return false;
	}

	public function isTaskInKanban(int $sprintId, int $taskId): bool
	{
		try
		{
			$stageIds = $this->getSprintStageIds($sprintId);

			$queryObject = TaskStageTable::getList([
				'filter' => [
					'TASK_ID' => $taskId,
					'STAGE_ID' => $stageIds
				]
			]);

			return ($queryObject->fetch() ? true : false);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK));
		}

		return false;
	}

	public function addTaskToNewStatus(int $sprintId, int $taskId): void
	{
		try
		{
			$newStageId = $this->getNewStageId($sprintId);

			if ($newStageId)
			{
				$this->removeTasksFromKanban($sprintId, [$taskId]);

				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => $newStageId
				]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK));
		}
	}

	/**
	 * Removes the tasks of the sprint.
	 *
	 * @param int $sprintId Sprint id.
	 * @param array $taskIds List task id.
	 * @return bool
	 */
	public function removeTasksFromKanban(int $sprintId, array $taskIds): bool
	{
		try
		{
			$stageIds = $this->getSprintStageIds($sprintId);

			foreach ($taskIds as $taskId)
			{
				$queryObject = TaskStageTable::getList([
					'filter' => [
						'TASK_ID' => $taskId,
						'STAGE_ID' => $stageIds
					]
				]);
				while ($taskStage = $queryObject->fetch())
				{
					TaskStageTable::delete($taskStage['ID']);
				}
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_TASK));
			return false;
		}
	}

	/**
	 * Gets default stages or stages of last sprint for active sprint.
	 *
	 * @param int $sprintId Sprint id for copy last view.
	 * @return array
	 */
	public function generateKanbanStages(int $sprintId = 0): array
	{
		$stages = [];

		try
		{
			if ($sprintId > 0)
			{
				if ($lastSprintId = $this->getLastCompletedSprintIdSameGroup($sprintId))
				{
					$stages = $this->getStagesCompletedSprint($lastSprintId);
				}
			}

			if ($stages)
			{
				return $stages;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_STAGES));
		}

		$stages = [
			'NEW' => [
				'COLOR' => '00C4FB',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_DEFAULT
			],
			'WORK' => [
				'COLOR' => '47D1E2',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_PROGRESS
			],
			'FINISH' => [
				'COLOR' => '75D900',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH
			]
		];

		return $stages;
	}

	public function getFinishedTaskIdsInSprint(int $sprintId): array
	{
		if (!isset($this->finishedTaskIdsCache[$sprintId]))
		{
			$this->finishedTaskIdsCache[$sprintId] = $this->getTaskIds([
				'=STAGE.SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH,
				'=STAGE.ENTITY_ID' => $sprintId,
				'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
			]);
		}
		return $this->finishedTaskIdsCache[$sprintId];
	}

	public function getUnfinishedTaskIdsInSprint(int $sprintId): array
	{
		if (!isset($this->unFinishedTaskIdsCache[$sprintId]))
		{
			$this->unFinishedTaskIdsCache[$sprintId] = $this->getTaskIds([
				'!=STAGE.SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH,
				'=STAGE.ENTITY_ID' => $sprintId,
				'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
			]);
		}
		return $this->unFinishedTaskIdsCache[$sprintId];
	}

	public function extractFinishedTaskIds(array $taskIds): array
	{
		$finishedTaskIds = [];

		foreach ($taskIds as $taskId)
		{
			if (
				$this->getTaskIds([
					'=STAGE.SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH,
					'=TASK_ID' => $taskId,
					'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				])
			)
			{
				$finishedTaskIds[] = $taskId;
			}
		}

		return $finishedTaskIds;
	}

	public function getKanbanSortValue(int $groupId): string
	{
		if (($row = ProjectsTable::getById($groupId)->fetch()))
		{
			return $row['ORDER_NEW_TASK'] ? $row['ORDER_NEW_TASK'] : 'actual';
		}
		else
		{
			return 'actual';
		}
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function getTaskIds(array $filter): array
	{
		$taskIds = [];

		try
		{
			$queryObject = TaskStageTable::getList([
				'select' => ['TASK_ID'],
				'filter' => $filter
			]);
			while ($taskStage = $queryObject->fetch())
			{
				if (!$this->isTaskInTheBasket($taskStage['TASK_ID']))
				{
					$taskIds[] = $taskStage['TASK_ID'];
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_TASKS));
		}

		return $taskIds;
	}

	public function getLastCompletedSprintIdSameGroup(int $sprintId): int
	{
		$queryObject = EntityTable::getList([
			'select' => ['ID', 'GROUP_ID'],
			'filter' => [
				'ID'=> (int) $sprintId
			]
		]);
		if ($sprintData = $queryObject->fetch())
		{
			$queryObjectLastSprint = EntityTable::getList([
				'select' => ['ID'],
				'filter' => [
					'!ID' => $sprintId,
					'GROUP_ID' => $sprintData['GROUP_ID'],
					'STATUS' => EntityTable::SPRINT_COMPLETED
				],
				'order' => ['ID' => 'DESC']
			]);
			return (($fields = $queryObjectLastSprint->fetch()) ? $fields['ID'] : 0);
		}
		return 0;
	}

	private function getStagesCompletedSprint(int $sprintId): array
	{
		$stages = [];

		$queryObject = StagesTable::getList([
			'select' => ['*'],
			'filter' => [
				'ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => $sprintId
			],
			'order' => [
				'SORT' => 'ASC'
			]
		]);
		while ($stage = $queryObject->fetch())
		{
			$stages[] = [
				'ID' => $stage['ID'],
				'TITLE' => $stage['TITLE'],
				'COLOR' => $stage['COLOR'],
				'SYSTEM_TYPE' => $stage['SYSTEM_TYPE']
			];
		}

		return $stages;
	}

	private function getSprintStageIds(int $sprintId): array
	{
		if (isset(self::$sprintStageIds[$sprintId]))
		{
			return self::$sprintStageIds[$sprintId];
		}

		self::$sprintStageIds[$sprintId] = [];

		$queryObject = StagesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => $sprintId,
			],
			'order' => ['SORT' => 'ASC']
		]);
		while ($stage = $queryObject->fetch())
		{
			self::$sprintStageIds[$sprintId][] = $stage['ID'];
		}

		return self::$sprintStageIds[$sprintId];
	}

	public function getStageIdsMapBetweenTwoSprints(int $firstSprintId, int $secondSprintId): array
	{
		$firstStages = [];
		$secondStages = [];

		$queryObject = StagesTable::getList([
			'select' => ['*'],
			'filter' => [
				'ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => [$firstSprintId, $secondSprintId],
			],
			'order' => ['SORT' => 'ASC']
		]);
		while ($stage = $queryObject->fetch())
		{
			if ($stage['ENTITY_ID'] == $firstSprintId)
			{
				$firstStages[] = $stage;
			}
			else if ($stage['ENTITY_ID'] == $secondSprintId)
			{
				$secondStages[] = $stage;
			}
		}

		$stageIdsMap = [];

		foreach ($firstStages as $firstStage)
		{
			foreach ($secondStages as $secondStage)
			{
				if ($firstStage['SORT'] === $secondStage['SORT'])
				{
					$stageIdsMap[$secondStage['ID']] = $firstStage['ID'];
				}
			}
		}

		return $stageIdsMap;
	}

	private function getFinishStageId(int $sprintId): int
	{
		try
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$stageId = 0;

			$stages = StagesTable::getStages($sprintId, true);
			foreach ($stages as $stage)
			{
				if ($stage['SYSTEM_TYPE'] == $this->getFinishStatus())
				{
					$stageId = (int) $stage['ID'];
				}
			}

			return $stageId;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_FINISH_STAGE)
			);
		}

		return 0;
	}

	private function getNewStageId(int $sprintId): int
	{
		try
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$stageId = 0;

			$stages = StagesTable::getStages($sprintId, true);
			foreach ($stages as $stage)
			{
				if ($stage['SYSTEM_TYPE'] == $this->getNewStatus())
				{
					$stageId = (int)$stage['ID'];
				}
			}

			return $stageId;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_FINISH_STAGE)
			);
		}

		return 0;
	}

	private function isTaskInTheBasket(int $taskId): bool
	{
		try
		{
			return TaskRecycleBin::isInTheRecycleBin($taskId);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHECK_IS_TASK_IN_BASKET)
			);
			return false;
		}
	}
}