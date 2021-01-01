<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
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

	private $errorCollection;

	private $unFinishedTaskIdsCache = [];
	private $finishedTaskIdsCache = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
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

			foreach ($taskIds as $taskId)
			{
				TaskStageTable::add([
					'TASK_ID' => $taskId,
					'STAGE_ID' => $defaultStageId
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

	public function getFinishStatus(): string
	{
		return StagesTable::SYS_TYPE_FINISH;
	}

	public function addTaskToFinishStatus(int $sprintId, int $taskId): void
	{
		try
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$finishStageId = $this->getFinishStageId($sprintId);

			if ($finishStageId)
			{
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

	/**
	 * Removes the tasks of the sprint.
	 *
	 * @param array $taskIds List task id.
	 * @return bool
	 */
	public function removeTasksFromKanban(array $taskIds): bool
	{
		try
		{
			foreach ($taskIds as $taskId)
			{
				TaskStageTable::clearTask($taskId);
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
	public function getKanbanStages(int $sprintId = 0): array
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
			if ($this->getTaskIds([
					'=STAGE.SYSTEM_TYPE' => StagesTable::SYS_TYPE_FINISH,
					'=TASK_ID' => $taskId,
					'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
				]
			))
			{
				$finishedTaskIds[] = $taskId;
			}
		}

		return $finishedTaskIds;
	}

	public function getKanbanSortValue(int $groupId): String
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
				$taskIds[] = $taskStage['TASK_ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_TASKS));
		}

		return $taskIds;
	}

	private function getLastCompletedSprintIdSameGroup(int $sprintId): int
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
				'TITLE' => $stage['TITLE'],
				'COLOR' => $stage['COLOR'],
				'SYSTEM_TYPE' => $stage['SYSTEM_TYPE']
			];
		}

		return $stages;
	}

	private function getFinishStageId(int $sprintId): int
	{
		try
		{
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
}