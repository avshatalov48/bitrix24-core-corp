<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EntityInfo;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\CacheService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\RobotService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Util\User;

class Sprint extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_SCS_01';
	const ERROR_ACCESS_DENIED = 'TASKS_SCS_02';
	const ERROR_COULD_NOT_START_SPRINT = 'TASKS_SCS_03';
	const ERROR_COULD_NOT_COMPLETE_SPRINT = 'TASKS_SCS_04';
	const ERROR_COULD_NOT_READ_SPRINT = 'TASKS_SCS_05';
	const ERROR_COULD_NOT_READ_ACTIVE_SPRINT = 'TASKS_SCS_06';

	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
		$userId = User::getId();

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * Returns sprint data for the sprint start form.
	 *
	 * @param int $groupId Group id.
	 * @param int $sprintId Sprint id.
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDataForSprintStartFormAction(int $groupId, int $sprintId): ?array
	{
		$userId = User::getId();

		$entityService = new EntityService();
		$sprintService = new SprintService($userId);
		$itemService = new ItemService();
		$taskService = new TaskService($userId);
		$kanbanService = new KanbanService();

		$sprint = $sprintService->getSprintById($sprintId);
		if ($sprint->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_COULD_NOT_READ_SPRINT'),
					self::ERROR_COULD_NOT_READ_SPRINT
				)
			);

			return null;
		}

		$sprintData = $sprintService->getSprintData($sprint);
		$sprintData['dateStart'] = ConvertTimeStamp($sprintData['dateStart']);
		$sprintData['dateEnd'] = ConvertTimeStamp($sprintData['dateEnd']);

		$sprintCounters = $entityService->getCounters(
			$groupId,
			$sprint->getId(),
			$taskService
		);
		$sprintData['numberTasks'] = $sprintCounters['countTotal'];
		$sprintData['storyPoints'] = (float) $sprintCounters['storyPoints'];
		$sprintData['numberUnevaluatedTasks'] = 0;

		$taskIds = $sprintCounters['taskIds'];

		$epics = [];
		foreach ($entityService->getItems($sprint->getId()) as $itemObject)
		{
			$item = new ItemForm();

			$item->fillFromDatabase($itemObject->collectValues());

			if (in_array($item->getSourceId(), $taskIds))
			{
				$itemData = $itemService->getItemData($item);

				if ($itemData['epicId'])
				{
					$epicData = $this->getEpicData($itemData['epicId']);
					if ($epicData)
					{
						$itemData['epic'] = $epics[$itemData['epicId']] = $epicData;
					}
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

	/**
	 * Returns sprint data for the sprint completion form.
	 *
	 * @param int $groupId Group id.
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDataForSprintCompletionFormAction(int $groupId): ?array
	{
		$userId = User::getId();

		$entityService = new EntityService();
		$sprintService = new SprintService($userId);
		$itemService = new ItemService();
		$taskService = new TaskService($userId);
		$kanbanService = new KanbanService();
		$userService = new UserService();

		$sprint = $sprintService->getActiveSprintByGroupId($groupId);
		if ($sprint->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_COULD_NOT_READ_ACTIVE_SPRINT'),
					self::ERROR_COULD_NOT_READ_ACTIVE_SPRINT
				)
			);

			return null;
		}

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
			$item = new ItemForm();

			$item->fillFromDatabase($itemObject->collectValues());

			$itemData = $itemService->getItemData($item);

			if (in_array($item->getSourceId(), $taskIds))
			{
				if ($itemData['epicId'])
				{
					$epicData = $this->getEpicData($itemData['epicId']);
					if ($epicData)
					{
						$itemData['epic'] = $epics[$itemData['epicId']] = $epicData;
					}
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

		$culture = Context::getCurrent()->getCulture();

		$sprintData['culture'] = [
			'dayMonthFormat' => stripslashes($culture->getDayMonthFormat()),
			'longDateFormat' => stripslashes($culture->getLongDateFormat()),
			'shortTimeFormat' => stripslashes($culture->getShortTimeFormat()),
		];

		return $sprintData;
	}

	/**
	 * Starts the sprint.
	 *
	 * @return string|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function startSprintAction(): ?string
	{
		$post = $this->request->getPostList()->toArray();

		$userId = User::getId();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		$sprintService = new SprintService();

		if (!$sprintService->canStartSprint($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
		$name = (is_string($post['name']) ? $post['name'] : '');
		$dateStart = (is_numeric($post['dateStart']) ? (int) $post['dateStart'] : 0);
		$dateEnd = (is_numeric($post['dateEnd']) ? (int) $post['dateEnd'] : 0);

		$sprint = $sprintService->getSprintById($sprintId);
		if ($sprint->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'),
					self::ERROR_COULD_NOT_START_SPRINT
				)
			);

			return null;
		}

		$sprint->setName($name);
		if ($dateStart)
		{
			$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
		}
		if ($dateEnd)
		{
			$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
		}

		$sprintInfo = new EntityInfo();
		if (!empty($post[$sprintInfo->getSprintGoalKey()]))
		{
			$sprintInfo->setSprintGoal($post[$sprintInfo->getSprintGoalKey()]);
		}
		$sprintInfo->setSprintStagesRecoveryStatusToWaiting();

		$sprint->setInfo($sprintInfo);

		$sprintService->changeSprint($sprint);

		$kanbanService = new KanbanService();
		$taskService = new TaskService($userId);
		$itemService = new ItemService();
		$backlogService = new BacklogService();
		$robotService = (Loader::includeModule('bizproc') ? new RobotService() : null);
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprintService->startSprint(
			$sprint,
			$taskService,
			$kanbanService,
			$itemService,
			$backlogService,
			$robotService,
			$pushService
		);

		if ($sprintService->getErrors())
		{
			$this->errorCollection->add($sprintService->getErrors());

			return null;
		}

		return '';
	}

	/**
	 * Completes the sprint.
	 *
	 * @param int $groupId Group id.
	 * @param string|int $direction Where to move unfinished tasks. Backlog or sprint.
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function completeSprintAction(int $groupId, $direction): ?array
	{
		$userId = User::getId();

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		$sprintService = new SprintService($userId);

		if (!$sprintService->canCompleteSprint($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$isTargetBacklog = ($direction === 'backlog');
		$targetSprintId = (is_numeric($direction) ? (int) $direction : 0);


		$entityService = new EntityService();
		$kanbanService = new KanbanService();
		$itemService = new ItemService();
		$taskService = new TaskService($userId);
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = $sprintService->getActiveSprintByGroupId($groupId);
		if ($sprint->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TSSC_ERROR_COULD_NOT_READ_ACTIVE_SPRINT'),
					self::ERROR_COULD_NOT_READ_ACTIVE_SPRINT
				)
			);

			return null;
		}

		$targetEntityId = $targetSprintId;
		if ($isTargetBacklog)
		{
			$backlogService = new BacklogService();

			$backlog = $backlogService->getBacklogByGroupId($sprint->getGroupId());
			$targetEntityId = $backlog->getId();
		}

		$sprint = $sprintService->completeSprint(
			$sprint,
			$entityService,
			$taskService,
			$kanbanService,
			$itemService,
			$targetEntityId,
			$pushService
		);

		if ($sprintService->getErrors())
		{
			$this->errorCollection->add($sprintService->getErrors());

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * Returns a data that the application might need.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 */
	public function getTeamSpeedInfoAction(int $groupId): array
	{
		$sprintService = new SprintService();

		$completedSprint = $sprintService->getLastCompletedSprint($groupId);

		return [
			'existsCompletedSprint' => !$completedSprint->isEmpty()
		];
	}

	/**
	 * Returns a data that the application might need.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 */
	public function getBurnDownInfoAction(int $groupId): array
	{
		$sprintService = new SprintService();

		$sprint = $sprintService->getActiveSprintByGroupId($groupId);

		if ($sprint->isEmpty())
		{
			$sprint = $sprintService->getLastCompletedSprint($groupId);
		}

		return [
			'sprint' => $sprint->isEmpty() ? null : $sprint->toArray()
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