<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Utility\SprintRanges;
use Bitrix\Tasks\Scrum\Utility\TimeHelper;
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
	const ERROR_COULD_NOT_CHANGE_SORT = 'TASKS_SS_09';
	const ERROR_COULD_NOT_READ_SPRINT_BY_GROUP = 'TASKS_SS_10';
	const ERROR_COULD_NOT_READ_COMPLETED_SPRINTS = 'TASKS_SS_11';
	const ERROR_COULD_NOT_READ_UNCOMPLETED_SPRINTS = 'TASKS_SS_12';
	const ERROR_COULD_NOT_READ_PLANNED_SPRINTS = 'TASKS_SS_13';
	const ERROR_COULD_NOT_READ_LAST_COMPLETED_SPRINT = 'TASKS_SS_14';
	const ERROR_COULD_NOT_READ_SPRINT_BY_ID = 'TASKS_SS_15';

	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createSprint(EntityForm $sprint, PushService $pushService = null): EntityForm
	{
		try
		{
			$result = EntityTable::add($sprint->getFieldsToCreateSprint());

			if ($result->isSuccess())
			{
				$sprint->setId($result->getId());

				if ($pushService)
				{
					$pushService->sendAddSprintEvent($sprint);
				}
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_SPRINT);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_SPRINT
				)
			);
		}

		return $sprint;
	}

	public function changeSprint(EntityForm $sprint, PushService $pushService = null): bool
	{
		try
		{
			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendUpdateSprintEvent($sprint);
				}

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
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_UPDATE_SPRINT
				)
			);

			return false;
		}
	}

	public function startSprint(
		EntityForm $sprint,
		TaskService $taskService,
		KanbanService $kanbanService,
		ItemService $itemService,
		BacklogService $backlogService,
		RobotService $robotService = null,
		PushService $pushService = null
	): EntityForm
	{
		try
		{
			$sprint->setStatus(EntityForm::SPRINT_ACTIVE);
			$sprint->setSort(0);

			$activeSprint = $this->getActiveSprintByGroupId($sprint->getGroupId());
			if (!$activeSprint->isEmpty())
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ALREADY_ERROR'))
				);

				return $sprint;
			}

			$allTaskIds = $itemService->getTaskIdsByEntityId($sprint->getId());
			if (empty($allTaskIds))
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_NOT_TASKS_ERROR'))
				);

				return $sprint;
			}

			$backlog = $backlogService->getBacklogByGroupId($sprint->getGroupId());

			$taskIds = [];
			$completedTaskIds = [];
			foreach ($allTaskIds as $taskId)
			{
				if ($taskService->isCompletedTask($taskId))
				{
					$completedTaskIds[] = $taskId;
				}
				else
				{
					$taskIds[] = $taskId;
				}
			}

			$completedSubTaskIds = [];
			foreach ($completedTaskIds as $taskId)
			{
				$completedSubTaskIds = array_merge(
					$completedSubTaskIds,
					$taskService->getSubTaskIds($sprint->getGroupId(), $taskId)
				);
			}
			$completedTaskIds = array_merge($completedTaskIds, $completedSubTaskIds);

			$itemIds = $itemService->getItemIdsBySourceIds($completedTaskIds, $sprint->getId());
			if (!$itemService->getErrors())
			{
				$itemService->moveItemsToEntity($itemIds, $backlog->getId());
			}

			$subTaskIds = [];
			foreach ($taskIds as $taskId)
			{
				$subTaskIds = array_merge($subTaskIds, $taskService->getSubTaskIds($sprint->getGroupId(), $taskId));
			}
			if ($taskService->getErrors())
			{
				$this->errorCollection->add($taskService->getErrors());

				return $sprint;
			}

			$kanbanService->addTasksToKanban($sprint->getId(), array_unique(array_merge($taskIds, $subTaskIds)));
			if ($kanbanService->getErrors())
			{
				$this->errorCollection->add($kanbanService->getErrors());

				return $sprint;
			}

			if ($robotService)
			{
				if ($lastSprintId = $kanbanService->getLastCompletedSprintIdSameGroup($sprint->getId()))
				{
					$stageIdsMap = $kanbanService->getStageIdsMapBetweenTwoSprints($sprint->getId(), $lastSprintId);

					$robotService->updateRobotsOfLastSprint($sprint->getGroupId(), $stageIdsMap);
				}

				if ($robotService->getErrors())
				{
					$this->errorCollection->add($robotService->getErrors());

					return $sprint;
				}
			}

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendUpdateSprintEvent($sprint);
				}
			}
			else
			{
				$this->errorCollection->setError(
					new Error(
						implode('; ', $result->getErrorMessages()),
						self::ERROR_COULD_NOT_START_SPRINT
					)
				);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_START_SPRINT)
			);
		}

		return $sprint;
	}

	public function completeSprint(
		EntityForm $sprint,
		EntityService $entityService,
		TaskService $taskService,
		KanbanService $kanbanService,
		ItemService $itemService,
		int $targetEntityId = 0,
		PushService $pushService = null
	): EntityForm
	{
		try
		{
			$sprint->setDateEnd(DateTime::createFromTimestamp(time()));
			$sprint->setStatus(EntityForm::SPRINT_COMPLETED);
			$sprint->setSort(0);

			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$unFinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

			$taskIdsToComplete = [];
			foreach ($finishedTaskIds as $finishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($finishedTaskId);
				if ($taskService->getErrors())
				{
					$this->errorCollection->add($taskService->getErrors());

					return $sprint;
				}

				if (!$isCompletedTask)
				{
					$taskIdsToComplete[] = $finishedTaskId;
				}
			}

			$taskService->completeTasks($taskIdsToComplete);
			if ($taskService->getErrors())
			{
				$this->errorCollection->add($taskService->getErrors());

				return $sprint;
			}

			foreach ($unFinishedTaskIds as $key => $unFinishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($unFinishedTaskId);
				if ($taskService->getErrors())
				{
					$this->errorCollection->add($taskService->getErrors());

					return $sprint;
				}

				if ($isCompletedTask)
				{
					$kanbanService->addTaskToFinishStatus($sprint->getId(), $unFinishedTaskId);
					unset($unFinishedTaskIds[$key]);
				}
			}

			if ($kanbanService->getErrors())
			{
				$this->errorCollection->add($kanbanService->getErrors());

				return $sprint;
			}

			if ($targetEntityId)
			{
				$entity = $entityService->getEntityById($targetEntityId);
			}
			else
			{
				$group = Workgroup::getById($sprint->getGroupId());

				$countSprints = count($this->getSprintsByGroupId($sprint->getGroupId()));

				$newSprint = new EntityForm();

				$newSprint->setGroupId($sprint->getGroupId());
				$newSprint->setName(Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => $countSprints + 1]));
				$newSprint->setSort(0);
				$newSprint->setCreatedBy($taskService->getUserId());
				$newSprint->setDateStart(DateTime::createFromTimestamp(time()));
				$newSprint->setDateEnd(DateTime::createFromTimestamp(time() + $group->getDefaultSprintDuration()));

				$entity = $this->createSprint($newSprint, $pushService);
			}

			foreach ($taskIdsToComplete as $taskIdToComplete)
			{
				if (!$taskService->isCompletedTask($taskIdToComplete))
				{
					$unFinishedTaskIds[] = $taskIdToComplete;
				}
			}

			$itemIds = $itemService->getItemIdsBySourceIds($unFinishedTaskIds, $sprint->getId());

			if (!$itemService->getErrors() && !$this->getErrors())
			{
				$itemService->moveItemsToEntity($itemIds, $entity->getId(), $pushService);
			}

			if ($itemService->getErrors())
			{
				$this->errorCollection->add($itemService->getErrors());

				return $sprint;
			}

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($pushService)
			{
				$pushService->sendUpdateSprintEvent($sprint);
			}

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
	 * @param ItemService|null $itemService Item Service.
	 * @param array $filteredSourceIds If you need to get filtered items.
	 * @return EntityForm
	 */
	public function getActiveSprintByGroupId(
		int $groupId,
		ItemService $itemService = null,
		array $filteredSourceIds = []
	): EntityForm
	{
		$sprint = new EntityForm();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID' => (int) $groupId,
					'=STATUS' => EntityForm::SPRINT_ACTIVE
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint->fillFromDatabase($sprintData);

				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint, null, $filteredSourceIds));
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

	/**
	 * Returns an array objects with sprints by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @return EntityForm []
	 */
	public function getSprintsByGroupId(int $groupId): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> $groupId,
					'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
				],
				'order' => [
					'SORT' => 'ASC',
					'DATE_END' => 'DESC',
				]
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = new EntityForm();

				$sprint->fillFromDatabase($sprintData);

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT_BY_GROUP)
			);
		}

		return $sprints;
	}

	public function getUncompletedSprints(
		int $groupId,
		PageNavigation $itemNav = null,
		ItemService $itemService = null,
		array $filteredSourceIds = []
	): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> $groupId,
					'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
					'!=STATUS' => EntityForm::SPRINT_COMPLETED,
				],
				'order' => [
					'SORT' => 'ASC',
					'DATE_END' => 'DESC',
				]
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = new EntityForm();

				$sprint->fillFromDatabase($sprintData);

				if ($itemService)
				{
					$sprint->setChildren(
						$itemService->getHierarchyChildItems($sprint, $itemNav, $filteredSourceIds)
					);
				}

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_UNCOMPLETED_SPRINTS)
			);
		}

		return $sprints;
	}

	public function getCompletedSprints(
		int $groupId,
		PageNavigation $sprintNav = null,
		ItemService $itemService = null,
		$filterInstance = null
	): array
	{
		$sprints = [];

		try
		{
			$query = new Query(EntityTable::getEntity());

			$query->setSelect(['*']);
			$query->setFilter([
				'GROUP_ID'=> $groupId,
				'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
				'=STATUS' => EntityForm::SPRINT_COMPLETED,
			]);
			$query->setOrder([
				'DATE_END' => 'DESC',
			]);

			$skipNavigation = ($filterInstance && $filterInstance->isSearchFieldApplied());

			if ($sprintNav && !$skipNavigation)
			{
				$query->setOffset($sprintNav->getOffset());
				$query->setLimit($sprintNav->getLimit() + 1);
			}

			$queryObject = $query->exec();

			$n = 0;
			while ($sprintData = $queryObject->fetch())
			{
				$n++;
				if ($sprintNav && !$skipNavigation && ($n > $sprintNav->getPageSize()))
				{
					break;
				}

				$sprint = new EntityForm();

				$sprint->fillFromDatabase($sprintData);

				$shouldGetItems = (!$filterInstance || $filterInstance->isSearchFieldApplied());

				if ($itemService && $shouldGetItems)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}

				$sprints[] = $sprint;
			}

			if ($sprintNav)
			{
				$sprintNav->setRecordCount($sprintNav->getOffset() + $n);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_COMPLETED_SPRINTS)
			);
		}

		return $sprints;
	}

	/**
	 * The method returns the planned sprints of the project.
	 *
	 * @param int $groupId Project id.
	 * @return EntityForm[]
	 */
	public function getPlannedSprints(int $groupId): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> $groupId,
					'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
					'=STATUS' => EntityForm::SPRINT_PLANNED,
				],
				'order' => [
					'SORT' => 'ASC',
					'DATE_END' => 'DESC',
				]
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = new EntityForm();

				$sprint->fillFromDatabase($sprintData);

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_PLANNED_SPRINTS)
			);
		}

		return $sprints;
	}

	/**
	 * Returns a last completed sprint by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @return EntityForm
	 */
	public function getLastCompletedSprint(int $groupId): EntityForm
	{
		$sprint = new EntityForm();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> (int) $groupId,
					'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
					'=STATUS' => EntityForm::SPRINT_COMPLETED
				],
				'order' => ['DATE_END' => 'DESC'],
				'limit' => 1
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint->fillFromDatabase($sprintData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_LAST_COMPLETED_SPRINT)
			);
		}

		return $sprint;
	}

	public function getSprintById(int $sprintId): EntityForm
	{
		$sprint = new EntityForm();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => (int) $sprintId
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint->fillFromDatabase($sprintData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT_BY_ID)
			);
		}

		return $sprint;
	}

	public function removeSprint(EntityForm $sprint, PushService $pushService = null): bool
	{
		try
		{
			$result = EntityTable::delete($sprint->getId());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendRemoveSprintEvent($sprint);
				}

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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_SPRINT)
			);

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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHANGE_SORT)
			);
		}
	}

	public function getCompletedStoryPoints(
		EntityForm $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());

		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	public function getUnCompletedStoryPoints(
		EntityForm $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$unfinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

		return $itemService->getSumStoryPointsBySourceIds($unfinishedTaskIds);
	}

	/**
	 * The method returns object with info about the time sprint days of the sprint.
	 *
	 * @param EntityForm $sprint
	 * @param Util\Calendar $calendar
	 * @return SprintRanges
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getSprintRanges(EntityForm $sprint, Util\Calendar $calendar): SprintRanges
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
	 * @param EntityForm $sprint Data object.
	 * @return array
	 */
	public function getSprintData(EntityForm $sprint): array
	{
		$info = $sprint->getInfo();

		$timeHelper = new TimeHelper($sprint->getCreatedBy());

		$dateStartTs = $sprint->getDateStart()->getTimestamp() + $timeHelper->getCurrentOffsetUTC();
		$dateEndTs = $sprint->getDateEnd()->getTimestamp() + $timeHelper->getCurrentOffsetUTC();

		return [
			'id' => $sprint->getId(),
			'tmpId' => $sprint->getTmpId(),
			'name' => $sprint->getName(),
			'sort' => $sprint->getSort(),
			'dateStartFormatted' => ConvertTimeStamp($dateStartTs),
			'dateEndFormatted' => ConvertTimeStamp($dateEndTs),
			'dateStart' => $dateStartTs,
			'dateEnd' => $dateEndTs,
			'weekendDaysTime' => $this->getWeekendDaysTime($sprint),
			'storyPoints' => '',
			'completedStoryPoints' => '',
			'uncompletedStoryPoints' => '',
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
		$this->errorCollection->setError(
			new Error(
				implode('; ', $result->getErrorMessages()),
				$code
			)
		);
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

	/**
	 * Returns the time of the next weekend to display the remaining days in the sprint.
	 *
	 * @param EntityForm $sprint
	 * @return int
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	private function getWeekendDaysTime(EntityForm $sprint): int
	{
		try
		{
			$calendar = new Util\Calendar();

			$sprintRanges = $this->getSprintRanges($sprint, $calendar);

			$weekendInfo = $sprintRanges->getWeekendInfo();
			$currentWeekDay = $sprintRanges->getCurrentWeekDay();

			foreach ($weekendInfo as $weekendNumber => $weekend)
			{
				if ($currentWeekDay && $currentWeekDay > $weekendNumber)
				{
					unset($weekendInfo[$weekendNumber]);
				}
			}

			$amountOfDays = count($weekendInfo);

			return ($amountOfDays * 86400);
		}
		catch (Exception $exception)
		{

		}

		return 0;
	}
}