<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Utility\SprintRanges;
use Bitrix\Tasks\Util;

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

				$pushService = new PushService();
				$pushService->sendAddSprintEvent($sprint);
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
				$pushService = new PushService();
				$pushService->sendUpdateSprintEvent($sprint);

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
				$pushService = new PushService();
				$pushService->sendUpdateSprintEvent($sprint);
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

			$pushService = new PushService();
			$pushService->sendUpdateSprintEvent($sprint);

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
				$sprint = EntityTable::createEntityObject($sprintData);
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
	 * @param Filter|null $filterInstance
	 * @return EntityTable []
	 */
	public function getSprintsByGroupId(int $groupId, ItemService $itemService = null, $filterInstance = null): array
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
				$sprint = EntityTable::createEntityObject($sprintData);

				$shouldGetItems = (
					!$sprint->isCompletedSprint() ||
					($filterInstance && $filterInstance->isSearchFieldApplied())
				);

				if ($itemService && $shouldGetItems)
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
				$sprint = EntityTable::createEntityObject($sprintData);

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
				$sprint = EntityTable::createEntityObject($sprintData);
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
				$sprint = EntityTable::createEntityObject($sprintData);
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
				$pushService = new PushService();
				$pushService->sendRemoveSprintEvent($sprint);

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
	 * @param Util\Calendar $calendar
	 * @return SprintRanges
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getSprintRanges(EntityTable $sprint, Util\Calendar $calendar): SprintRanges
	{
		$info = [
			'all' => [],
			'weekdays' => [],
			'weekendInfo' => [],
			'currentWeekDay' => 0,
		];

		$start = (new \DateTime())->setTimestamp($sprint->getDateStart()->getTimestamp());
		$end = (new \DateTime())->setTimestamp($sprint->getDateEnd()->getTimestamp());

		$currentDateTime = new \Datetime();

		$period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
		$weekDayNumber = 0;
		foreach ($period as $key => $value)
		{
			$dayNumber = $key + 1;
			$value->add(new \DateInterval('PT9H'));
			if ($calendar->isWeekend(Util\Type\DateTime::createFromTimestamp($value->getTimestamp())))
			{
				$info['weekendInfo'][$dayNumber] = [
					'weekendNumber' => $dayNumber,
					'previousWeekday' => ($weekDayNumber ? $weekDayNumber : 1)
				];
			}
			else
			{
				$weekDayNumber = $dayNumber;
				$info['weekdays'][$dayNumber] = $value->getTimestamp();

				$weekDayRange = [
					'start' => strtotime('today', $value->getTimestamp()),
					'end' => strtotime('tomorrow', $value->getTimestamp()) - 1,
				];
				$currentDayRange = [
					'start' => strtotime('today', $currentDateTime->getTimestamp()),
					'end' => strtotime('tomorrow', $currentDateTime->getTimestamp()) - 1,
				];
				if ($this->isTimeOverlapping($weekDayRange, $currentDayRange))
				{
					$info['currentWeekDay'] = $dayNumber;
				}
			}
			$info['all'][$dayNumber] = $value->getTimestamp();
		}

		$sprintRanges = new SprintRanges();

		$sprintRanges->setAllDays($info['all']);
		$sprintRanges->setWeekdays($info['weekdays']);
		$sprintRanges->setWeekendInfo($info['weekendInfo']);
		$sprintRanges->setCurrentWeekDay($info['currentWeekDay']);

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
				if ($this->isTimeOverlapping($sprintDayRange, $taskCompleteTimeDayRange))
				{
					$mapCompletedTasks[$dayNumber][] = $completedTaskId;
				}
			}
		}

		return $mapCompletedTasks;
	}

	private function isTimeOverlapping(array $firstRange, array $secondRange): bool
	{
		return (
			(
				$firstRange['start'] <= $secondRange['end'] &&
				$firstRange['start'] >= $secondRange['start']
			)
			|| (
				$firstRange['end'] <= $secondRange['end'] &&
				$firstRange['end'] >= $secondRange['start']
			)
		);
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

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param EntityTable $sprint Data object.
	 * @return array
	 */
	public function getSprintData(EntityTable $sprint): array
	{
		$info = $sprint->getInfo();

		return [
			'id' => $sprint->getId(),
			'tmpId' => $sprint->getTmpId(),
			'name' => $sprint->getName(),
			'sort' => $sprint->getSort(),
			'dateStart' => $sprint->getDateStart()->getTimestamp(),
			'dateEnd' => $sprint->getDateEnd()->getTimestamp(),
			'weekendDaysTime' => $this->getWeekendDaysTime($sprint),
			'totalStoryPoints' => $sprint->getStoryPoints(),
			'totalCompletedStoryPoints' => '',
			'totalUncompletedStoryPoints' => '',
			'completedTasks' => 0,
			'uncompletedTasks' => 0,
			'status' => $sprint->getStatus(),
			'numberTasks' => 0,
			'items' => [],
			'views' => [],
			'info' => $info->getInfoData(),
			'isExactSearchApplied' => 'N'
		];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}

	private function getWeekendDaysTime(EntityTable $sprint): int
	{
		try
		{
			$calendar = new Util\Calendar();
			$sprintRanges = $this->getSprintRanges($sprint, $calendar);
			$amountOfDays = count($sprintRanges->getWeekendInfo());
			return ($amountOfDays * 86400);
		}
		catch (Exception $exception) {}

		return 0;
	}
}