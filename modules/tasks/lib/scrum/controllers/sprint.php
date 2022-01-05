<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Internal\EntityInfoColumn;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\CacheService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\RobotService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Scrum\Utility\BurnDownChart;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;

class Sprint extends Controller
{
	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->errorCollection = new ErrorCollection;
	}

	public function getDataForSprintStartFormAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$entityService = new EntityService();
			$sprintService = new SprintService();
			$itemService = new ItemService();
			$taskService = new TaskService($userId);
			$kanbanService = new KanbanService();

			$sprint = $sprintService->getSprintById($sprintId);

			$sprintData = $sprintService->getSprintData($sprint);
			$sprintData['dateStart'] = ConvertTimeStamp($sprintData['dateStart']);
			$sprintData['dateEnd'] = ConvertTimeStamp($sprintData['dateEnd']);

			$sprintCounters = $entityService->getCounters(
				$groupId,
				$sprint->getId(),
				$taskService
			);
			$sprintData['numberTasks'] = $sprintCounters['countTotal'];
			$sprintData['storyPoints'] = $sprintCounters['storyPoints'];
			$sprintData['numberUnevaluatedTasks'] = 0;

			$taskIds = $sprintCounters['taskIds'];

			$epics = [];
			foreach ($entityService->getItems($sprint->getId()) as $itemObject)
			{
				$item = ItemTable::createItemObject($itemObject->collectValues());

				if (in_array($item->getSourceId(), $taskIds))
				{
					$itemData = $itemService->getItemData($item);

					if ($itemData['epicId'])
					{
						$itemData['epic'] = $epics[$itemData['epicId']] = $this->getEpicData($itemData['epicId']);
					}

					if ($item->getStoryPoints() === '')
					{
						$sprintData['numberUnevaluatedTasks']++;
					}
				}
			}
			$sprintData['epics'] = array_values($epics);

			$lastStoryPoints = 0;
			$lastCompletedSprint = $sprintService->getLastCompletedSprint($groupId);
			if (!$lastCompletedSprint->isEmpty())
			{
				$lastStoryPoints = $sprintService->getCompletedStoryPoints(
						$lastCompletedSprint,
						$kanbanService,
						$itemService
					)
					+ $sprintService->getUnCompletedStoryPoints(
						$lastCompletedSprint,
						$kanbanService,
						$itemService
					);
			}
			$differenceStoryPoints = $sprintData['storyPoints'] - $lastStoryPoints;
			$sprintData['differenceMarker'] = $differenceStoryPoints >= 0;
			$sprintData['differenceStoryPoints'] = ($differenceStoryPoints > 0 ? '+' : '') . $differenceStoryPoints;

			return $sprintData;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getDataForSprintCompletionFormAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$entityService = new EntityService();
			$sprintService = new SprintService();
			$itemService = new ItemService();
			$taskService = new TaskService($userId);
			$kanbanService = new KanbanService();
			$userService = new UserService();

			$sprint = $sprintService->getActiveSprintByGroupId($groupId, $itemService);

			$sprintData = $sprintService->getSprintData($sprint);

			$sprintCounters = $entityService->getCounters(
				$groupId,
				$sprint->getId(),
				$taskService,
				false
			);

			$sprintData['storyPoints'] = $sprintCounters['storyPoints'];

			$taskIds = $sprintCounters['taskIds'];
			$uncompletedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

			$uncompletedTasks = [];
			$epics = [];
			foreach ($entityService->getItems($sprint->getId()) as $itemObject)
			{
				$item = ItemTable::createItemObject($itemObject->collectValues());

				$itemData = $itemService->getItemData($item);

				if (in_array($item->getSourceId(), $taskIds))
				{
					if ($itemData['epicId'])
					{
						$itemData['epic'] = $epics[$itemData['epicId']] = $this->getEpicData($itemData['epicId']);
					}
				}

				if (in_array($item->getSourceId(), $uncompletedTaskIds))
				{
					$uncompletedTasks[$item->getSourceId()] = $itemData;
				}
			}
			$sprintData['epics'] = array_values($epics);

			$uncompletedTasksIds = array_keys($uncompletedTasks);
			foreach ($taskService->getItemsData($uncompletedTasksIds) as $taskId => $taskData)
			{
				if (isset($taskData['responsibleId']))
				{
					$taskData['responsible'] = $userService->getInfoAboutUsers([$taskData['responsibleId']]);
				}

				$taskData['tags'] = [];

				$uncompletedTasks[$taskId] = array_merge($uncompletedTasks[$taskId], $taskData);
			}
			foreach ($taskService->getTags($uncompletedTasksIds) as $taskId => $tags)
			{
				$uncompletedTasks[$taskId]['tags'] = $tags;
			}
			$sprintData['uncompletedTasks'] = array_values($uncompletedTasks);

			$completedStoryPoints = $sprintService->getCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);
			$sprintData['completedStoryPoints'] = $completedStoryPoints;

			$lastStoryPoints = 0;
			$lastCompletedStoryPoints = 0;
			$lastCompletedSprint = $sprintService->getLastCompletedSprint($groupId);
			if (!$lastCompletedSprint->isEmpty())
			{
				$sprintData['existsLastSprint'] = true;

				$lastCompletedStoryPoints = $sprintService->getCompletedStoryPoints(
					$lastCompletedSprint,
					$kanbanService,
					$itemService
				);
				$lastStoryPoints = $completedStoryPoints
					+ $sprintService->getUnCompletedStoryPoints(
						$lastCompletedSprint,
						$kanbanService,
						$itemService
					);
			}
			else
			{
				$sprintData['existsLastSprint'] = false;
			}
			$sprintData['lastStoryPoints'] = $lastStoryPoints;
			$sprintData['lastCompletedStoryPoints'] = $lastCompletedStoryPoints;

			$sprintData['plannedSprints'] = [];
			foreach ($sprintService->getPlannedSprints($groupId) as $plannedSprint)
			{
				$sprintData['plannedSprints'][] = [
					'id' => $plannedSprint->getId(),
					'name' => $plannedSprint->getName(),
				];
			}

			return $sprintData;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getBurnDownChartDataAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$inputSprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$sprintService = new SprintService();

			$sprint = $sprintService->getSprintById($inputSprintId);

			if ($sprint->isActiveSprint())
			{
				$currentDateTime = new Datetime();
				$currentDateEnd = $sprint->getDateEnd();
				$sprint->setDateEnd($currentDateEnd > $currentDateTime ? $currentDateEnd : $currentDateTime);
			}

			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$taskService = new TaskService($userId);

			$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$uncompletedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
			$taskIds = array_merge($completedTaskIds, $uncompletedTaskIds);

			$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($taskIds);

			$storyPointsService = new StoryPoints();
			$sumStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);

			$calendar = new Util\Calendar();
			$sprintRanges = $sprintService->getSprintRanges($sprint, $calendar);

			$completedTasksMap = $sprintService->getCompletedTasksMap(
				$sprintRanges,
				$taskService,
				$completedTaskIds
			);
			$completedStoryPointsMap = $sprintService->getCompletedStoryPointsMap(
				$sumStoryPoints,
				$completedTasksMap,
				$itemsStoryPoints
			);

			$burnDownChart = new BurnDownChart();
			$idealData = $burnDownChart->prepareIdealBurnDownChartData($sumStoryPoints, $sprintRanges);
			$remainingData = $burnDownChart->prepareRemainBurnDownChartData(
				$sumStoryPoints,
				$sprintRanges,
				$completedStoryPointsMap
			);

			return array_merge($idealData, $remainingData);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getTeamSpeedChartDataAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$sprintService = new SprintService();
			$cacheService = new CacheService($groupId, CacheService::TEAM_SPEED_CHART);

			if ($cacheService->init())
			{
				$data = $cacheService->getData();
			}
			else
			{
				$itemService = new ItemService();
				$kanbanService = new KanbanService();

				$cacheService->start();

				$data = [];

				foreach ($sprintService->getCompletedSprintsByGroupId($groupId) as $sprint)
				{
					$completedPoints = $sprintService->getCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					);

					$uncompletedPoints = $sprintService->getUnCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					);

					$data[] = [
						'sprintName' => $sprint->getName(),
						'plan' => round(($completedPoints + $uncompletedPoints), 2),
						'done' => $completedPoints,
					];
				}

				$cacheService->end($data);
			}

			return $data;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function startSprintAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$sprintService = new SprintService();

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
			$name = (is_string($post['name']) ? $post['name'] : '');
			$dateStart = (is_numeric($post['dateStart']) ? (int) $post['dateStart'] : 0);
			$dateEnd = (is_numeric($post['dateEnd']) ? (int) $post['dateEnd'] : 0);

			$sprint = EntityTable::createEntityObject();

			$sprint->setId($sprintId);
			$sprint->setName($name);
			$sprint->setGroupId($groupId);
			if ($dateStart)
			{
				$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
			}
			if ($dateEnd)
			{
				$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
			}

			$sprintInfo = new EntityInfoColumn();
			if (!empty($post[$sprintInfo->getSprintGoalKey()]))
			{
				$sprintInfo->setSprintGoal($post[$sprintInfo->getSprintGoalKey()]);
			}
			$sprint->setInfo($sprintInfo);

			$itemService = new ItemService();
			$kanbanService = new KanbanService();

			if ($sprintService->isActiveSprint($sprint->getId()))
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ALREADY_ERROR'))
				);

				return null;
			}

			$allTaskIds = $itemService->getTaskIdsByEntityId($sprint->getId());
			if (empty($allTaskIds))
			{
				$this->errorCollection->setError(
					new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_NOT_TASKS_ERROR'))
				);

				return null;
			}

			$taskService = new TaskService($userId);
			$backlogService = new BacklogService();

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
					$taskService->getSubTaskIds($groupId, $taskId)
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
				$subTaskIds = array_merge($subTaskIds, $taskService->getSubTaskIds($groupId, $taskId));
			}
			if ($taskService->getErrors())
			{
				$this->errorCollection->add($taskService->getErrors());

				return null;
			}

			$kanbanService->addTasksToKanban($sprint->getId(), $taskIds);
			$kanbanService->addTasksToKanban($sprint->getId(), $subTaskIds);
			if ($kanbanService->getErrors())
			{
				$this->errorCollection->add($kanbanService->getErrors());

				return null;
			}

			if (Loader::includeModule('bizproc'))
			{
				$robotService = new RobotService();

				if ($lastSprintId = $kanbanService->getLastCompletedSprintIdSameGroup($sprint->getId()))
				{
					$stageIdsMap = $kanbanService->getStageIdsMapBetweenTwoSprints($sprint->getId(), $lastSprintId);

					$robotService->updateRobotsOfLastSprint($groupId, $stageIdsMap);
				}

				if ($robotService->getErrors())
				{
					$this->errorCollection->add($robotService->getErrors());

					return null;
				}
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprintService->startSprint($sprint, $pushService);

			if ($this->getErrors())
			{
				$this->errorCollection->add($this->getErrors());

				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function completeSprintAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$isTargetBacklog = ($post['direction'] === 'backlog');
			$targetSprintId = (is_numeric($post['direction']) ? (int) $post['direction'] : 0);

			$sprintService = new SprintService();
			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$backlogService = new BacklogService();

			$sprint = $sprintService->getActiveSprintByGroupId($groupId);

			$sprint->setDateEnd(DateTime::createFromTimestamp(time()));

			$taskService = new TaskService($userId);

			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$unFinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

			$taskIdsToComplete = [];
			foreach ($finishedTaskIds as $key => $finishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($finishedTaskId);
				if ($taskService->getErrors())
				{
					$this->errorCollection->add($taskService->getErrors());

					return null;
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

				return null;
			}

			foreach ($unFinishedTaskIds as $key => $unFinishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($unFinishedTaskId);
				if ($taskService->getErrors())
				{
					$this->errorCollection->add($taskService->getErrors());

					return null;
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

				return null;
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			if ($isTargetBacklog)
			{
				$entity = $backlogService->getBacklogByGroupId($sprint->getGroupId());
			}
			else
			{
				if ($targetSprintId)
				{
					$entity = $sprintService->getSprintById($targetSprintId);
				}
				else
				{
					$group = Workgroup::getById($groupId);

					$countSprints = count($sprintService->getSprintsByGroupId($groupId));

					$newSprint = EntityTable::createEntityObject();

					$newSprint->setGroupId($sprint->getGroupId());
					$newSprint->setName(
						Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => $countSprints + 1])
					);
					$newSprint->setSort(0);
					$newSprint->setCreatedBy($userId);
					$newSprint->setDateStart(DateTime::createFromTimestamp(time()));
					$newSprint->setDateEnd(
						DateTime::createFromTimestamp(time() + $group->getDefaultSprintDuration())
					);

					$entity = $sprintService->createSprint($newSprint, $pushService);
				}
			}

			$itemIds = $itemService->getItemIdsBySourceIds($unFinishedTaskIds, $sprint->getId());

			if (!$itemService->getErrors() && !$this->getErrors() && !$backlogService->getErrors())
			{
				$pushService = (Loader::includeModule('pull') ? new PushService() : null);
				$itemService->moveItemsToEntity($itemIds, $entity->getId(), $pushService);
			}

			$sprintService->completeSprint($sprint, $pushService);
			if ($this->getErrors())
			{
				$this->errorCollection->add($this->getErrors());

				return null;
			}

			(new CacheService($groupId, CacheService::TEAM_SPEED_CHART))->clean();

			return ['movedItemIds' => $itemIds];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getTeamSpeedInfoAction()
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		$userId = User::getId();

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			return null;
		}

		$sprintService = new SprintService();

		$completedSprint = $sprintService->getLastCompletedSprint($groupId);

		return [
			'existsCompletedSprint' => !$completedSprint->isEmpty()
		];
	}

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
	}

	private function getEpicData(int $epicId): array
	{
		$epicService = new EpicService();

		$cacheService = new CacheService($epicId, CacheService::EPICS);

		if ($cacheService->init())
		{
			return $cacheService->getData();
		}
		else
		{
			$epic = $epicService->getEpic($epicId);

			return $epic->getId() ? $epic->toArray() : [];
		}
	}
}