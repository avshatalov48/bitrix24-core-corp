<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Recyclebin;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\ProjectsTable;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Internal\EntityTable;

class KanbanService implements Errorable
{
	const ERROR_COULD_NOT_ADD_TASK = 'TASKS_KS_01';
	const ERROR_COULD_NOT_REMOVE_TASK = 'TASKS_KS_02';
	const ERROR_COULD_NOT_GET_LAST_STAGES = 'TASKS_KS_03';
	const ERROR_COULD_NOT_ADD_ONE_TASK = 'TASKS_KS_05';

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

		if ($entity->getEntityType() === EntityForm::BACKLOG_TYPE)
		{
			return [];
		}

		return $this->getStages($entity->getId());
	}

	public function getStages(int $sprintId): array
	{
		$stages = [];

		$queryObject = StagesTable::getList([
			'filter' => [
				'ENTITY_ID' => $sprintId,
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
			],
			'order' => [
				'SORT' => 'ASC',
			],
		]);
		while ($stage = $queryObject->fetch())
		{
			if ($stage['COLOR'] == '')
			{
				$stage['COLOR'] = StagesTable::DEF_COLOR_STAGE;
			}

			if ($stage['TITLE'] == '')
			{
				if ($stage['SYSTEM_TYPE'] != '')
				{
					$stage['TITLE'] = $this->getDefaultStageTitle($stage['SYSTEM_TYPE']);
				}
				else
				{
					$stage['TITLE'] = $this->getDefaultStageTitle(StagesTable::SYS_TYPE_DEFAULT);
				}
			}

			$stages[$stage['ID']] = $stage;
		}

		return $stages;
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

		if ($entity->getEntityType() === EntityForm::BACKLOG_TYPE)
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
	 * @param int $lastSprintId Last sprint id only for start new sprint.
	 * @return bool
	 */
	public function addTasksToKanban(int $sprintId, array $taskIds, int $lastSprintId = 0): bool
	{
		try
		{
			if (empty($taskIds))
			{
				return false;
			}

			$defaultStageId = $this->getDefaultStageId($sprintId);
			if (!$defaultStageId)
			{
				$this->errorCollection->setError(
					new Error('Failed to get the default stage', self::ERROR_COULD_NOT_ADD_TASK)
				);

				return false;
			}

			$taskStageIdsMap = [];
			if ($lastSprintId)
			{
				$stageIdsMap = $this->getStageIdsMapBetweenTwoSprints($sprintId, $lastSprintId);
				if ($stageIdsMap)
				{
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
			}

			$this->removeTasksFromKanban($sprintId, $taskIds);

			foreach ($taskIds as $taskId)
			{
				$stageId = ($taskStageIdsMap[$taskId] ?? $defaultStageId);

				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => $stageId,
				]);

				$taskObject = new \CTasks;
				$taskObject->update($taskId, ['STAGE_ID' => $stageId]);
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_TASK)
			);

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

				$taskObject = new \CTasks;
				$taskObject->update($taskId, ['STAGE_ID' => $finishStageId]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK)
			);
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK)
			);
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK)
			);
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

				$taskObject = new \CTasks;
				$taskObject->update($taskId, ['STAGE_ID' => $newStageId]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ONE_TASK)
			);
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_TASK)
			);

			return false;
		}
	}

	public function removeStages(int $sprintId): void
	{
		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		$queryObject = StagesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => $sprintId,
			],
			'order' => ['ID' => 'ASC']
		]);
		while ($stage = $queryObject->fetch())
		{
			StagesTable::delete($stage['ID'], $sprintId);
		}
	}

	/**
	 * Gets default stages or stages of last sprint for active sprint.
	 *
	 * @param int $lastSprintId Last sprint id for copy last stages.
	 * @return array
	 */
	public function generateKanbanStages(int $lastSprintId = 0): array
	{
		if ($lastSprintId)
		{
			$stages = $this->getStagesCompletedSprint($lastSprintId);

			if ($stages)
			{
				return $stages;
			}
			else
			{
				$this->errorCollection->setError(
					new Error(
						'Failed to get last completed sprint',
						self::ERROR_COULD_NOT_GET_LAST_STAGES
					)
				);

				return [];
			}
		}

		return [
			'NEW' => [
				'TITLE' => $this->getDefaultStageTitle(StagesTable::SYS_TYPE_DEFAULT),
				'COLOR' => '00C4FB',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_DEFAULT
			],
			'WORK' => [
				'TITLE' => $this->getDefaultStageTitle(StagesTable::SYS_TYPE_PROGRESS),
				'COLOR' => '47D1E2',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_PROGRESS
			],
			'FINISH' => [
				'TITLE' => $this->getDefaultStageTitle(StagesTable::SYS_TYPE_FINISH),
				'COLOR' => '75D900',
				'SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH
			]
		];
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
		return $this->getTaskIds([
			'=STAGE.SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH,
			'=TASK_ID' => $taskIds,
			'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
		]);
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

	public function getStageTitles(array $stageIds): array
	{
		$stageTitles = [];
		foreach ($stageIds as $stageId)
		{
			$stageTitles[$stageId] = '';
		}

		$res = StagesTable::getList([
			'select' => ['ID', 'TITLE', 'SYSTEM_TYPE'],
			'filter' => ['@ID' => $stageIds],
		]);
		while ($stage = $res->fetch())
		{
			$stageId = (int) $stage['ID'];
			$stageKey = array_search($stageId, $stageIds);
			if ($stageKey !== false && $stageId === $stageIds[$stageKey])
			{
				if ($stage['TITLE'] === '')
				{
					if ($stage['SYSTEM_TYPE'] !== '')
					{
						$title = $this->getDefaultStageTitle($stage['SYSTEM_TYPE']);
					}
					else
					{
						$title = $this->getDefaultStageTitle(StagesTable::SYS_TYPE_DEFAULT);
					}
				}
				else
				{
					$title = $stage['TITLE'];
				}

				$stageTitles[$stageId] = $title;
			}
		}

		return $stageTitles;
	}

	public function moveTask(int $taskId, int $stageId): bool
	{
		$itemService = new ItemService();
		$entityService = new EntityService();

		$scrumItem = $itemService->getItemBySourceId($taskId);
		if ($itemService->getErrors() || $scrumItem->isEmpty())
		{
			return false;
		}

		$entity = $entityService->getEntityById($scrumItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return false;
		}

		if ($entity->getEntityType() === EntityForm::BACKLOG_TYPE)
		{
			return false;
		}

		$taskObject = new \CTasks;

		$queryObject = TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $taskId,
				'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'STAGE.ENTITY_ID' => $entity->getId()
			]
		]);
		if ($taskStage = $queryObject->fetch())
		{
			TaskStageTable::update($taskStage['ID'], [
				'STAGE_ID' => $stageId,
			]);

			$taskObject->update($taskId, ['STAGE_ID' => $stageId]);
		}

		return true;
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
				'select' => ['ID', 'DATE_END'],
				'filter' => [
					'GROUP_ID'=> (int) $sprintData['GROUP_ID'],
					'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
					'=STATUS' => EntityForm::SPRINT_COMPLETED
				],
				'order' => ['DATE_END' => 'DESC'],
				'limit' => 1
			]);

			return (($fields = $queryObjectLastSprint->fetch()) ? $fields['ID'] : 0);
		}

		return 0;
	}

	public function getStageIdsMapBetweenTwoSprints(int $firstSprintId, int $secondSprintId): array
	{
		$firstStages = [];
		$secondStages = [];

		$firstSprintId = (int) $firstSprintId;
		$secondSprintId = (int) $secondSprintId;

		$queryObject = StagesTable::getList([
			'select' => ['*'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => [$firstSprintId, $secondSprintId],
			],
			'order' => ['SORT' => 'ASC']
		]);
		while ($stage = $queryObject->fetch())
		{
			$entityId = (int) $stage['ENTITY_ID'];

			if ($entityId === $firstSprintId)
			{
				$firstStages[] = $stage;
			}
			else if ($entityId === $secondSprintId)
			{
				$secondStages[] = $stage;
			}
		}

		$stageIdsMap = [];

		if (count($firstStages) !== count($secondStages))
		{
			return $stageIdsMap;
		}

		foreach ($firstStages as $firstStage)
		{
			foreach ($secondStages as $secondStage)
			{
				if (
					$firstStage['TITLE'] === $secondStage['TITLE']
					&& $firstStage['SYSTEM_TYPE'] === $secondStage['SYSTEM_TYPE']
				)
				{
					$stageIdsMap[$secondStage['ID']] = $firstStage['ID'];
				}
			}
		}

		return $stageIdsMap;
	}

	public function createSprintStages(int $sprintId, int $lastSprintId = 0)
	{
		$stages = $this->generateKanbanStages($lastSprintId);

		$sort = 0;
		foreach ($stages as $stageCode => $stageItem)
		{
			StagesTable::add(
				[
					'SYSTEM_TYPE' => array_key_exists('SYSTEM_TYPE', $stageItem)
						? $stageItem['SYSTEM_TYPE']
						: $stageCode
					,
					'TITLE' => array_key_exists('TITLE', $stageItem) ? $stageItem['TITLE'] : '',
					'SORT' => ++$sort * 100,
					'ENTITY_ID' => $sprintId,
					'ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
					'COLOR' => $stageItem['COLOR']
				]
			);
		}
	}

	public function hasSprintStages(int $sprintId): bool
	{
		$queryObject = StagesTable::getList([
			'select' => ['*'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => $sprintId
			],
			'order' => [
				'SORT' => 'ASC'
			]
		]);

		return (bool) $queryObject->fetch();
	}

	/**
	 * Checks if the number of columns differs between sprints.
	 *
	 * @param int $firstSprintId First sprint id.
	 * @param int $secondSprintId Second sprint id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function hasDifferencesBetweenTwoSprints(int $firstSprintId, int $secondSprintId): bool
	{
		$firstStages = [];
		$secondStages = [];

		$firstSprintId = (int) $firstSprintId;
		$secondSprintId = (int) $secondSprintId;

		$queryObject = StagesTable::getList([
			'select' => ['ENTITY_ID'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'ENTITY_ID' => [$firstSprintId, $secondSprintId],
			],
			'order' => ['SORT' => 'ASC']
		]);
		while ($stage = $queryObject->fetch())
		{
			$entityId = (int) $stage['ENTITY_ID'];

			if ($entityId === $firstSprintId)
			{
				$firstStages[] = $stage;
			}
			else if ($entityId === $secondSprintId)
			{
				$secondStages[] = $stage;
			}
		}

		return count($firstStages) !== count($secondStages);
	}

	public function getDefaultStageId(int $sprintId): int
	{
		foreach ($this->getStages($sprintId) as $stage)
		{
			if ($stage['SYSTEM_TYPE'] == $this->getNewStatus())
			{
				return $stage['ID'];
			}
		}

		return 0;
	}

	private function getDefaultStageTitle(string $systemType): string
	{
		return Loc::getMessage('TASKS_SCRUM_STAGE_' . $systemType);
	}

	private function getTaskIds(array $filter): array
	{
		$taskIds = [];

		$queryObject = TaskStageTable::getList([
			'select' => ['TASK_ID'],
			'filter' => $filter
		]);
		while ($taskStage = $queryObject->fetch())
		{
			$taskIds[$taskStage['TASK_ID']] = $taskStage['TASK_ID'];
		}

		if (empty($taskIds))
		{
			return [];
		}

		foreach ($this->isTasksInBasket($taskIds) as $taskId => $result)
		{
			if ($result === true)
			{
				unset($taskIds[$taskId]);
			}
		}

		return array_values($taskIds);
	}

	private function getStagesCompletedSprint(int $sprintId): array
	{
		$stages = [];

		$queryObject = StagesTable::getList([
			'select' => ['*'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
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

	public function getSprintStageIds(int $sprintId): array
	{
		if (isset(self::$sprintStageIds[$sprintId]))
		{
			return self::$sprintStageIds[$sprintId];
		}

		self::$sprintStageIds[$sprintId] = [];

		$queryObject = StagesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
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

	private function getFinishStageId(int $sprintId): int
	{
		$stageId = 0;

		$stages = $this->getStages($sprintId);
		foreach ($stages as $stage)
		{
			if ($stage['SYSTEM_TYPE'] == $this->getFinishStatus())
			{
				$stageId = (int) $stage['ID'];
			}
		}

		return $stageId;
	}

	private function getNewStageId(int $sprintId): int
	{
		$stageId = 0;

		$stages = $this->getStages($sprintId);
		foreach ($stages as $stage)
		{
			if ($stage['SYSTEM_TYPE'] == $this->getNewStatus())
			{
				$stageId = (int)$stage['ID'];
			}
		}

		return $stageId;
	}

	private function isTasksInBasket(array $taskIds): array
	{
		return Recyclebin\Task::isInTheRecycleBin($taskIds);
	}
}