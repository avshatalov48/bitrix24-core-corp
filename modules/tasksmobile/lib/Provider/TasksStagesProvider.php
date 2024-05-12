<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\TasksMobile\Dto\TaskStageDto;
use Bitrix\TasksMobile\Enum\ViewMode;
use Bitrix\Tasks\Integration\SocialNetwork;

final class TasksStagesProvider
{
	private const PERIOD_OVERDUE = 'PERIOD1';
	private const PERIOD_NO_DEADLINE = 'PERIOD5';
	private const PERIOD_OVER_TWO_WEEKS = 'PERIOD6';

	private ?string $workMode = null;
	private ?int $stageId = null;
	private ?int $projectId = null;
	private ?int $userId = null;
	private ?int $entityId = null;
	private string $viewMode;
	private bool $canMoveStage;

	public function __construct(
		?string $workMode = null,
		?int $stageId = null,
		?int $projectId = null,
		?int $userId = null,
	)
	{
		$this->workMode = $workMode;
		$this->stageId = $stageId;
		$this->projectId = $projectId;
		$this->userId = $userId ?? CurrentUser::get()->getId();
		$this->viewMode = ViewMode::resolveByWorkMode($workMode);
		$this->entityId = $workMode === StagesTable::WORK_MODE_GROUP ? $projectId : $userId;
		$this->canMoveStage = $this->verifyCanMoveStage();
	}

	/**
	 * @param array<int, array{ID: int, STAGE_ID: int}> $tasks
	 * @return TaskStageDto[]
	 */
	public function getStages(array $tasks): array
	{
		if ($this->stageId)
		{
			return $this->fillWithPredefinedStage($tasks);
		}

		if ($this->workMode === StagesTable::WORK_MODE_GROUP)
		{
			return $this->fillWithProjectStages($tasks);
		}

		if ($this->workMode === StagesTable::WORK_MODE_USER)
		{
			return $this->fillWithPlannerStages($tasks);
		}

		if ($this->workMode === StagesTable::WORK_MODE_TIMELINE)
		{
			return $this->fillWithDeadlineStages($tasks);
		}

		return [];
	}

	/**
	 * @param array<int, array{ID: int, STAGE_ID: int}> $tasks
	 * @return TaskStageDto[]
	 */
	private function fillWithPredefinedStage(array $tasks): array
	{
		$result = [];

		foreach ($tasks as $task)
		{
			$result[] = TaskStageDto::make([
				'taskId' => (int)$task['ID'],
				'stageId' => $this->stageId,
				'userId' => $this->userId,
				'viewMode' => $this->viewMode,
				'canMoveStage' => $this->canMoveStage,
			]);
		}

		return $result;
	}

	/**
	 * @param array<int, array{ID: int, STAGE_ID: int}> $tasks
	 * @return TaskStageDto[]
	 */
	private function fillWithProjectStages(array $tasks): array
	{
		$result = [];

		foreach ($tasks as $task)
		{
			$result[] = TaskStageDto::make([
				'taskId' => (int)$task['ID'],
				'stageId' => (int)$task['STAGE_ID'],
				'userId' => $this->userId,
				'viewMode' => $this->viewMode,
				'canMoveStage' => $this->canMoveStage,
			]);
		}

		return $result;
	}

	/**
	 * @param array<int, array{ID: int, STAGE_ID: int}> $tasks
	 * @return TaskStageDto[]
	 */
	private function fillWithPlannerStages(array $tasks): array
	{
		$possibleStageIds = array_keys($this->fetchPossibleStages());
		$taskIds = array_keys($tasks);

		if (empty($possibleStageIds) || empty($taskIds))
		{
			return [];
		}

		$result = [];

		$rows = TaskStageTable::getList([
			'select' => ['TASK_ID', 'STAGE_ID'],
			'filter' => [
				'TASK_ID' => $taskIds,
				'STAGE_ID' => $possibleStageIds,
			],
		]);
		while ($row = $rows->fetch())
		{
			$result[] = TaskStageDto::make([
				'taskId' => (int)$row['TASK_ID'],
				'stageId' => (int)$row['STAGE_ID'],
				'userId' => $this->userId,
				'viewMode' => $this->viewMode,
				'canMoveStage' => $this->canMoveStage,
			]);
		}

		return $result;
	}

	/**
	 * @param array<int, array{ID: int, STAGE_ID: int}> $tasks
	 * @return TaskStageDto[]
	 */
	private function fillWithDeadlineStages(array $tasks): array
	{
		$possibleStages = $this->fetchPossibleStages();
		$deadlines = [];
		$periods = [];
		$result = [];

		foreach ($possibleStages as $stageId => $stage)
		{
			$periods[$stage['SYSTEM_TYPE']] = $stageId;
			if ($this->isStageIncludesDeadlineFilter($stage))
			{
				$deadlines[$stage['SYSTEM_TYPE']] = $stage['ADDITIONAL_FILTER'];
			}
		}

		unset($stageId);

		foreach ($tasks as $task)
		{
			if ($task['DEADLINE'])
			{
				$taskDeadline = strtotime($task['DEADLINE']);
				$now = time();
				$stageId = null;

				if (!$taskDeadline)
				{
					$stageId = $periods[self::PERIOD_NO_DEADLINE];
				}
				elseif ($taskDeadline <= $now)
				{
					$stageId = $periods[self::PERIOD_OVERDUE];
				}
				else
				{
					foreach ($deadlines as $period => $deadlineFilter)
					{
						if ($this->isDeadlineMatchesFilter($taskDeadline, $deadlineFilter))
						{
							$stageId = $periods[$period];
							break;
						}
					}
					if ($stageId === null)
					{
						$stageId = $periods[self::PERIOD_OVER_TWO_WEEKS];
					}
				}
			}
			else
			{
				$stageId = $periods[self::PERIOD_NO_DEADLINE];
			}

			$result[] = TaskStageDto::make([
				'taskId' => (int)$task['ID'],
				'stageId' => (int)$stageId,
				'userId' => $this->userId,
				'viewMode' => $this->viewMode,
				'canMoveStage' => $this->canMoveStage,
			]);
		}

		return $result;
	}

	private function fetchPossibleStages(): array
	{
		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($this->workMode);
		$possibleStages = StagesTable::getStages($this->entityId);
		StagesTable::setWorkMode($prevWorkMode);

		return $possibleStages;
	}

	private function isStageIncludesDeadlineFilter(array $stage): bool
	{
		if (!isset($stage['ADDITIONAL_FILTER']))
		{
			return false;
		}

		$deadlineFilter = $stage['ADDITIONAL_FILTER'];

		return isset($deadlineFilter['>DEADLINE']) || isset($deadlineFilter['<=DEADLINE']);
	}

	private function isDeadlineMatchesFilter(int $taskDeadline, array $deadlineFilter): bool
	{
		if (isset($deadlineFilter['>DEADLINE']))
		{
			$leftBorder = (new DateTime($deadlineFilter['>DEADLINE']))->getTimestamp();
			if ($taskDeadline <= $leftBorder)
			{
				return false;
			}
		}

		if (isset($deadlineFilter['<=DEADLINE']))
		{
			$rightBorder = (new DateTime($deadlineFilter['<=DEADLINE']))->getTimestamp();
			if ($taskDeadline > $rightBorder)
			{
				return false;
			}
		}

		return true;
	}

	private function verifyCanMoveStage(): bool
	{
		$entityId = (int)$this->entityId;
		if (!$entityId)
		{
			return false;
		}

		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return false;
		}

		if (
			$this->workMode === StagesTable::WORK_MODE_GROUP
			&& !SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_SORT_TASKS)
		)
		{
			return false;
		}

		if (
			$this->workMode !== StagesTable::WORK_MODE_GROUP
			&& $entityId !== $this->userId
		)
		{
			return false;
		}

		return true;
	}
}
