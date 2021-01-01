<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Utility\SprintRanges;
use Bitrix\Tasks\Util\Calendar as TaskCalendar;
use Bitrix\Tasks\Util\Type\DateTime as TasksDateTime;

class SprintService implements Errorable
{
	const ERROR_COULD_NOT_ADD_SPRINT = 'TASKS_SS_01';
	const ERROR_COULD_NOT_UPDATE_SPRINT = 'TASKS_SS_02';
	const ERROR_COULD_NOT_READ_SPRINT = 'TASKS_SS_03';
	const ERROR_COULD_NOT_REMOVE_SPRINT = 'TASKS_SS_04';
	const ERROR_COULD_NOT_START_SPRINT = 'TASKS_SS_05';
	const ERROR_COULD_NOT_COMPLETE_SPRINT = 'TASKS_SS_06';
	const ERROR_COULD_NOT_READ_ACTIVE_SPRINT = 'TASKS_SS_07';
	const ERROR_COULD_NOT_DETECT_ACTIVE_SPRINT = 'TASKS_SS_08';
	const ERROR_COULD_NOT_CHANGE_SORT = 'TASKS_SS_09';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createSprint(EntityTable $sprint): EntityTable
	{
		try
		{
			$result = EntityTable::add($sprint->getFieldsToCreateSprint());

			if ($result->isSuccess())
			{
				$sprint->setId($result->getId());
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_SPRINT);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_SPRINT));
		}

		return $sprint;
	}

	public function changeSprint(EntityTable $sprint): bool
	{
		try
		{
			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_UPDATE_SPRINT);
				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_UPDATE_SPRINT));
			return false;
		}
	}

	public function startSprint(EntityTable $sprint, KanbanService $kanbanService): EntityTable
	{
		try
		{
			$sprint->setStatus(EntityTable::SPRINT_ACTIVE);
			$sprint->setSort(0);

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				$kanbanService->addTasksToKanban($sprint->getId(), $sprint->getTaskIds());
				if ($kanbanService->getErrors())
				{
					$this->errorCollection->add($kanbanService->getErrors());
				}
			}
			else
			{
				$this->errorCollection->setError(new Error(
					implode('; ', $result->getErrorMessages()),
					self::ERROR_COULD_NOT_START_SPRINT
				));
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_START_SPRINT));
		}

		return $sprint;
	}

	public function completeSprint(EntityTable $sprint): EntityTable
	{
		try
		{
			$sprint->setStatus(EntityTable::SPRINT_COMPLETED);
			$sprint->setSort(0);

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if (!$result->isSuccess())
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_COMPLETE_SPRINT);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_COMPLETE_SPRINT)
			);
		}

		return $sprint;
	}

	/**
	 * Gets active sprint by group id.
	 *
	 * @param int $groupId Group id.
	 * @param ItemService $itemService Item Service.
	 * @return EntityTable
	 */
	public function getActiveSprintByGroupId(int $groupId, ItemService $itemService = null): EntityTable
	{
		$sprint = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID' => (int) $groupId,
					'STATUS' => EntityTable::SPRINT_ACTIVE
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);
				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ACTIVE_SPRINT)
			);
		}

		return $sprint;
	}

	public function isActiveSprint(EntityTable $sprint): bool
	{
		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> $sprint->getGroupId(),
					'STATUS' => EntityTable::SPRINT_ACTIVE
				]
			]);
			return (bool) $queryObject->fetch();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_DETECT_ACTIVE_SPRINT)
			);
			return false;
		}
	}

	/**
	 * Returns an array objects with sprints by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @param ItemService|null $itemService Item service object, if you need to fill with items.
	 * @return EntityTable []
	 */
	public function getSprintsByGroupId(int $groupId, ItemService $itemService = null): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> (int) $groupId,
					'ENTITY_TYPE' => EntityTable::SPRINT_TYPE
				],
				'order' => ['SORT' => 'ASC', 'DATE_END' => 'DESC']
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = EntityTable::createEntityObject();

				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);

				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprints;
	}

	/**
	 * Returns an array objects with completed sprints by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @param ItemService|null $itemService Item service object, if you need to fill with items.
	 * @return EntityTable []
	 */
	public function getCompletedSprintsByGroupId(int $groupId, ItemService $itemService = null): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> (int) $groupId,
					'ENTITY_TYPE' => EntityTable::SPRINT_TYPE,
					'STATUS' => EntityTable::SPRINT_COMPLETED
				],
				'order' => ['DATE_END' => 'ASC']
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = EntityTable::createEntityObject();

				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);

				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprints;
	}

	/**
	 * Returns a last completed sprint by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @return EntityTable
	 */
	public function getLastCompletedSprint(int $groupId): EntityTable
	{
		$sprint = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> (int) $groupId,
					'ENTITY_TYPE' => EntityTable::SPRINT_TYPE,
					'STATUS' => EntityTable::SPRINT_COMPLETED
				],
				'order' => ['DATE_END' => 'DESC'],
				'limit' => 1
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprint;
	}

	public function getSprintById(int $sprintId): EntityTable
	{
		$sprint = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => (int) $sprintId
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprint;
	}

	public function removeSprint(EntityTable $sprint): bool
	{
		try
		{
			$result = EntityTable::delete($sprint->getId());
			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE_SPRINT);
				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_SPRINT));
			return false;
		}
	}

	public function changeSort(array $sortInfo): void
	{
		try
		{
			$sprintIds = [];
			$whens = [];

			foreach ($sortInfo as $sprintId => $info)
			{
				$sprintId = (is_numeric($sprintId) ? (int) $sprintId : 0);
				if ($sprintId)
				{
					$sort = (is_numeric($info['sort']) ? (int) $info['sort'] : 0);
					$sprintIds[] = $sprintId;
					$whens[] = 'WHEN ID = '.$sprintId.' THEN '.$sort;
				}
			}

			if ($sprintIds)
			{
				$expression = new SqlExpression('(CASE '.implode(' ', $whens).' END)');

				EntityTable::updateMulti($sprintIds, [
					'SORT' => $expression
				]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHANGE_SORT));
		}
	}

	public function getCompletedStoryPoints(
		EntityTable $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	public function getUnCompletedStoryPoints(
		EntityTable $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$finishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	/**
	 * The method returns object with info about the time sprint days of the sprint.
	 *
	 * @param EntityTable $sprint
	 * @param TaskCalendar $calendar
	 * @return SprintRanges
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getSprintRanges(EntityTable $sprint, TaskCalendar $calendar): SprintRanges
	{
		$info = [
			'all' => [],
			'weekdays' => [],
			'weekendInfo' => []
		];

		$start = (new \DateTime())->setTimestamp($sprint->getDateStart()->getTimestamp());
		$end = (new \DateTime())->setTimestamp($sprint->getDateEnd()->getTimestamp());

		$period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
		$dayNumber = 0;
		foreach ($period as $key => $value)
		{
			$value->add(new \DateInterval('PT9H'));
			if ($calendar->isWeekend(TasksDateTime::createFromTimestamp($value->getTimestamp())))
			{
				$info['weekendInfo'][$key + 1] = [
					'weekendNumber' => $key + 1,
					'previousWeekday' => ($dayNumber ? $dayNumber : 1)
				];
			}
			else
			{
				$info['weekdays'][++$dayNumber] = $value->getTimestamp();
			}
			$info['all'][$key + 1] = $value->getTimestamp();
		}

		$sprintRanges = new SprintRanges();

		$sprintRanges->setAllDays($info['all']);
		$sprintRanges->setWeekdays($info['weekdays']);
		$sprintRanges->setWeekendInfo($info['weekendInfo']);

		return $sprintRanges;
	}

	public function getCompletedTasksMap(
		SprintRanges $sprintRanges,
		TaskService $taskService,
		array $completedTaskIds
	): array
	{
		$mapCompletedTasks = [];

		$sprintDayRanges = [];
		$taskCompleteTimeDayRanges = [];

		foreach ($sprintRanges->getAllDays() as $dayNumber => $dayTime)
		{
			$mapCompletedTasks[$dayNumber] = [];

			$sprintDayRanges[$dayNumber] = [
				'start' => strtotime('today', $dayTime),
				'end' => strtotime('tomorrow', $dayTime) - 1
			];
		}

		foreach ($completedTaskIds as $completedTaskId)
		{
			$taskClosedDate = $taskService->getTaskClosedDate($completedTaskId);
			if ($taskClosedDate)
			{
				$taskClosedTime = $taskClosedDate->getTimestamp();
			}
			else
			{
				$taskClosedTime = $sprintRanges->getLastSprintDayTime();
			}

			$taskCompleteTimeDayRanges[$completedTaskId] = [
				'start' => strtotime('today', $taskClosedTime),
				'end' => strtotime('tomorrow', $taskClosedTime) - 1
			];
		}

		foreach ($sprintDayRanges as $dayNumber => $sprintDayRange)
		{
			foreach ($taskCompleteTimeDayRanges as $completedTaskId => $taskCompleteTimeDayRange)
			{
				$isOverlapping = (
					(
						$sprintDayRange['start'] <= $taskCompleteTimeDayRange['end'] &&
						$sprintDayRange['start'] >= $taskCompleteTimeDayRange['start']
					) ||
					(
						$sprintDayRange['end'] <= $taskCompleteTimeDayRange['end'] &&
						$sprintDayRange['end'] >= $taskCompleteTimeDayRange['start']
					)
				);
				if ($isOverlapping)
				{
					$mapCompletedTasks[$dayNumber][] = $completedTaskId;
				}
			}
		}

		$maxDayNumber = count($sprintRanges->getWeekdays());
		foreach ($mapCompletedTasks as $dayNumber => $completedTasks)
		{
			if ($dayNumber > $maxDayNumber)
			{
				$mapCompletedTasks[$maxDayNumber] = array_merge($mapCompletedTasks[$maxDayNumber], $completedTasks);
				unset($mapCompletedTasks[$dayNumber]);
			}
		}

		return $mapCompletedTasks;
	}

	public function getCompletedStoryPointsMap(
		float $sumStoryPoints,
		array $mapCompletedTasks,
		array $itemsStoryPoints
	): array
	{
		$mapCompletedStoryPoints = [];

		$completedStoryPoints = 0;
		foreach ($mapCompletedTasks as $dayNumber => $completedTasks)
		{
			foreach ($completedTasks as $taskId)
			{
				if (isset($itemsStoryPoints[$taskId]))
				{
					$completedStoryPoints += (float) $itemsStoryPoints[$taskId];
				}
			}
			$mapCompletedStoryPoints[$dayNumber] = $sumStoryPoints - $completedStoryPoints;
		}

		return $mapCompletedStoryPoints;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function fillSprintObjectByTableData(EntityTable $sprint, array $sprintData): EntityTable
	{
		$sprint->setId($sprintData['ID']);
		$sprint->setGroupId($sprintData['GROUP_ID']);
		$sprint->setEntityType($sprintData['ENTITY_TYPE']);
		$sprint->setName($sprintData['NAME']);
		if ($sprintData['SORT'])
		{
			$sprint->setSort($sprintData['SORT']);
		}
		$sprint->setCreatedBy($sprintData['CREATED_BY']);
		$sprint->setModifiedBy($sprintData['MODIFIED_BY']);
		if ($sprintData['DATE_START'])
		{
			$sprint->setDateStart($sprintData['DATE_START']);
		}
		if ($sprintData['DATE_END'])
		{
			$sprint->setDateEnd($sprintData['DATE_END']);
		}
		$sprint->setStatus($sprintData['STATUS']);
		if ($sprintData['INFO'])
		{
			$sprint->setInfo($sprintData['INFO']);
		}
		return $sprint;
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}
}