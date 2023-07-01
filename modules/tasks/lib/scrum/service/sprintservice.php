<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Utility\SprintRanges;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
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
	const ERROR_COULD_NOT_CHANGE_SORT = 'TASKS_SS_09';
	const ERROR_COULD_NOT_READ_SPRINT_BY_GROUP = 'TASKS_SS_10';
	const ERROR_COULD_NOT_READ_PLANNED_SPRINTS = 'TASKS_SS_13';
	const ERROR_COULD_NOT_READ_LAST_COMPLETED_SPRINT = 'TASKS_SS_14';
	const ERROR_COULD_NOT_READ_SPRINT_BY_ID = 'TASKS_SS_15';

	protected $errorCollection;

	private $userId;

	private static $allowedActions = [];

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;

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

			$lastSprintId = $kanbanService->getLastCompletedSprintIdSameGroup($sprint->getId());
			if (!$kanbanService->hasSprintStages($sprint->getId()))
			{
				$kanbanService->createSprintStages($sprint->getId(), $lastSprintId);
			}
			if ($kanbanService->getErrors())
			{
				$this->errorCollection->add($kanbanService->getErrors());

				return $sprint;
			}

			$allTaskIds = $itemService->getTaskIdsByEntityId($sprint->getId());

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

			$itemIds = $itemService->getItemIdsBySourceIds($completedTaskIds, [$sprint->getId()]);
			if (!$itemService->getErrors())
			{
				$itemService->moveItemsToEntity($itemIds, $backlog->getId(), $pushService);
			}

			$subTaskIds = [];
			foreach ($taskIds as $taskId)
			{
				$subTaskIds = array_merge(
					$subTaskIds,
					$taskService->getSubTaskIds($sprint->getGroupId(), $taskId)
				);
			}
			if ($taskService->getErrors())
			{
				$this->errorCollection->add($taskService->getErrors());

				return $sprint;
			}

			if (empty($taskIds))
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_NOT_TASKS_ERROR'))
				);

				return $sprint;
			}

			$kanbanService->addTasksToKanban(
				$sprint->getId(),
				array_unique(array_merge($taskIds, $subTaskIds)),
				$lastSprintId
			);
			if ($kanbanService->getErrors())
			{
				$this->errorCollection->add($kanbanService->getErrors());

				return $sprint;
			}

			if ($lastSprintId && $robotService)
			{
				$stageIdsMap = $kanbanService->getStageIdsMapBetweenTwoSprints(
					$sprint->getId(),
					$lastSprintId
				);
				if ($stageIdsMap)
				{
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

				if (
					!$isCompletedTask
					&& TaskAccessController::can(
						$this->userId,
						ActionDictionary::ACTION_TASK_COMPLETE,
						$finishedTaskId
					)
				)
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

			$itemIds = $itemService->getItemIdsBySourceIds($unFinishedTaskIds, [$sprint->getId()]);

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

			(new CacheService($sprint->getGroupId(), CacheService::STATS))->clean();
			(new CacheService($sprint->getGroupId(), CacheService::TEAM_STATS))->cleanRoot();
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
	 * @return EntityForm
	 */
	public function getActiveSprintByGroupId(int $groupId): EntityForm
	{
		$sprint = new EntityForm();

		$queryObject = EntityTable::getList([
			'filter' => [
				'GROUP_ID' => (int) $groupId,
				'=STATUS' => EntityForm::SPRINT_ACTIVE
			],
		]);
		if ($sprintData = $queryObject->fetch())
		{
			$sprint->fillFromDatabase($sprintData);
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

	public function getUncompletedSprints(int $groupId): array
	{
		$sprints = [];

		$queryObject = EntityTable::getList([
			'filter' => [
				'GROUP_ID'=> $groupId,
				'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
				'!=STATUS' => EntityForm::SPRINT_COMPLETED,
			],
			'order' => [
				'DATE_START' => 'ASC',
			]
		]);
		while ($sprintData = $queryObject->fetch())
		{
			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$sprints[] = $sprint;
		}

		return $sprints;
	}

	public function getCompletedSprints(int $groupId, PageNavigation $sprintNav = null): array
	{
		$sprints = [];

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

		if ($sprintNav)
		{
			$query->setOffset($sprintNav->getOffset());
			$query->setLimit($sprintNav->getLimit() + 1);
		}

		$queryObject = $query->exec();

		$n = 0;
		while ($sprintData = $queryObject->fetch())
		{
			$n++;
			if ($sprintNav && ($n > $sprintNav->getPageSize()))
			{
				break;
			}

			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$sprints[] = $sprint;
		}

		if ($sprintNav)
		{
			$sprintNav->setRecordCount($sprintNav->getOffset() + $n);
		}

		return $sprints;
	}

	/**
	 * The method returns the end date of the last planned sprint.
	 *
	 * @param int $groupId Group id.
	 * @return int|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDateEndFromLastPlannedSprint(int $groupId): ?int
	{
		$sprint = new EntityForm();

		$queryObject = EntityTable::getList([
			'select' => ['ID', 'DATE_END'],
			'filter' => [
				'GROUP_ID'=> $groupId,
				'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
				'=STATUS' => EntityForm::SPRINT_PLANNED,
			],
			'order' => [
				'DATE_START' => 'DESC',
			]
		]);
		if ($data = $queryObject->fetch())
		{
			$sprint->fillFromDatabase($data);
		}

		if ($sprint->isEmpty())
		{
			return null;
		}

		return $sprint->getDateEnd()->getTimestamp();
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
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ_SPRINT_BY_ID
				)
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

		if (empty($finishedTaskIds))
		{
			return 0;
		}

		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	public function getUnCompletedStoryPoints(
		EntityForm $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$unfinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

		if (empty($unfinishedTaskIds))
		{
			return 0;
		}

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

		$averageNumberStoryPoints = 0;
		if ($sprint->isPlannedSprint())
		{
			$storyPoints = new StoryPoints();

			$averageNumberStoryPoints = $storyPoints
				->calculateAverageNumberCompletedStoryPoints($sprint->getGroupId())
			;
		}

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
			'averageNumberStoryPoints' => $averageNumberStoryPoints,
			'items' => [],
			'views' => [],
			'info' => $info->getInfoData(),
			'isExactSearchApplied' => 'N',
			'allowedActions' => [
				'start' => $this->canStartSprint($this->userId, $sprint->getGroupId()),
				'complete' => $this->canCompleteSprint($this->userId, $sprint->getGroupId()),
			],
			'isShownContent' => (
				($sprint->isShownContent($this->userId) && !$sprint->isCompletedSprint())
					? 'Y'
					: 'N'
			),
		];
	}

	public function canStartSprint(int $userId, int $groupId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if ($userId === 0)
		{
			return false;
		}

		$key = $userId . $groupId;
		if (
			array_key_exists($key, self::$allowedActions)
			&& self::$allowedActions[$key]
		)
		{
			return self::$allowedActions[$key];
		}

		$userRoleInGroup = \CSocNetUserToGroup::getUserRole($userId, $groupId);

		if (
			$userRoleInGroup == SONET_ROLES_MODERATOR
			|| $userRoleInGroup == SONET_ROLES_OWNER
			|| \CSocNetUser::isCurrentUserModuleAdmin()
		)
		{
			self::$allowedActions[$key] = true;
		}
		else
		{
			self::$allowedActions[$key] = false;
		}

		return self::$allowedActions[$key];
	}

	public function canCompleteSprint(int $userId, int $groupId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if ($userId === 0)
		{
			return false;
		}

		$key = $userId . $groupId;
		if (self::$allowedActions[$key] ?? null)
		{
			return self::$allowedActions[$key];
		}

		$userRoleInGroup = \CSocNetUserToGroup::getUserRole($userId, $groupId);

		if (
			$userRoleInGroup == SONET_ROLES_MODERATOR
			|| $userRoleInGroup == SONET_ROLES_OWNER
			|| \CSocNetUser::isCurrentUserModuleAdmin()
		)
		{
			self::$allowedActions[$key] = true;
		}
		else
		{
			self::$allowedActions[$key] = false;
		}

		return self::$allowedActions[$key];
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