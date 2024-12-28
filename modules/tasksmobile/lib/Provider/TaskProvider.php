<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Crm\Integration\UI\EntitySelector\DynamicMultipleProvider;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Util\User;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Socialnetwork\Item\UserContentView;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\FileUploader\TaskController;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\TasksMobile\TextFragmentParser;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Provider\Exception\TaskListException;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\TasksMobile\Dto\DiskFileDto;
use Bitrix\TasksMobile\Dto\TaskDto;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\UserField\Provider\TaskUserFieldProvider;
use Bitrix\TasksMobile\UserField\Type;
use Bitrix\UI\FileUploader\Uploader;
use CTaskAssertException;
use CTaskDependence;
use TasksException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Main\Type\Collection;

final class TaskProvider
{
	public const COUNTER_NONE = 'none';
	public const COUNTER_EXPIRED = 'expired';
	public const COUNTER_NEW_COMMENTS = 'new_comments';

	public const PRESET_NONE = 'none';
	public const ORDER_ACTIVITY = 'ACTIVITY';
	public const ORDER_DEADLINE = 'DEADLINE';

	private int $userId;
	private string $order;
	private array $extra;
	private ?PageNavigation $pageNavigation;
	private ?TaskRequestFilter $searchParams;

	/**
	 * @param int $userId
	 * @param string $order
	 * @param array $extra
	 * @param TaskRequestFilter|null $searchParams
	 * @param PageNavigation|null $pageNavigation
	 */
	public function __construct(
		int $userId,
		string $order = TaskProvider::ORDER_ACTIVITY,
		array $extra = [],
		?TaskRequestFilter $searchParams = null,
		?PageNavigation $pageNavigation = null,
	)
	{
		$this->userId = $userId;
		$this->searchParams = ($searchParams ?? new TaskRequestFilter());
		$this->order = $order;
		$this->extra = $extra;
		$this->pageNavigation = $pageNavigation;
	}

	public function getUserListTasks(): array
	{
		return $this->getTasksWithMinimalInputParams();
	}

	public function getUserPlannerTasks(?int $stageId): array
	{
		return $this->getTasksWithMinimalInputParams(StagesTable::WORK_MODE_USER, $stageId);
	}

	public function getUserDeadlineTasks(?int $stageId): array
	{
		return $this->getTasksWithMinimalInputParams(StagesTable::WORK_MODE_TIMELINE, $stageId);
	}

	public function getProjectListTasks(int $projectId): array
	{
		return $this->getTasksWithMinimalInputParams(null, null, $projectId);
	}

	public function getProjectPlannerTasks(int $projectId, ?int $stageId): array
	{
		return $this->getTasksWithMinimalInputParams(StagesTable::WORK_MODE_USER, $stageId, $projectId);
	}

	public function getProjectDeadlineTasks(int $projectId, ?int $stageId): array
	{
		return $this->getTasksWithMinimalInputParams(StagesTable::WORK_MODE_TIMELINE, $stageId, $projectId);
	}

	public function getProjectKanbanTasks(int $projectId, ?int $stageId): array
	{
		return $this->getTasksWithMinimalInputParams(StagesTable::WORK_MODE_GROUP, $stageId, $projectId);
	}

	/**
	 * @param string|null $workMode
	 * @param int|null $stageId
	 * @param int|null $projectId
	 * @return array
	 */
	private function getTasksWithMinimalInputParams(
		?string $workMode = null,
		?int $stageId = null,
		?int $projectId = null
	): array
	{
		$select = $this->getSelect();
		$filter = $this->getFilter($workMode, $stageId, $projectId);
		$order = $this->getOrder($workMode);
		$params = $this->getParams($projectId);

		$tasks = $this->getTasks($select, $filter, $order, $params, $this->pageNavigation);

		return [
			'items' => $this->prepareItems($tasks),
			'users' => $this->getUsersData($tasks),
			'groups' => $this->getGroupsData($tasks, $projectId),
			'flows' => $this->getFlowsData($tasks),
			'tasks_stages' => $this->getStagesData($tasks, $workMode, $stageId, $projectId),
		];
	}

	private function getStagesData(
		array $tasks,
		?string $workMode = null,
		?int $stageId = null,
		?int $projectId = null
	): array
	{
		if (!$workMode)
		{
			return [];
		}

		$provider = new TasksStagesProvider($workMode, $stageId, $projectId, $this->searchParams->ownerId);

		return $provider->getStages($tasks);
	}

	private function getSelect(): array
	{
		return [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'STATUS',
			'REAL_STATUS',
			'GROUP_ID',
			'FLOW_ID',
			'PARENT_ID',
			'PRIORITY',
			'IS_MUTED',
			'IS_PINNED',
			'FAVORITE',

			'CREATED_BY',
			'RESPONSIBLE_ID',
			'ACCOMPLICES',
			'AUDITORS',

			'DEADLINE',
			'ACTIVITY_DATE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'DATE_START',
			'CLOSED_DATE',

			'COUNTERS',
			'STAGE_ID',
			'COMMENTS_COUNT',
			'SERVICE_COMMENTS_COUNT',
			'MARK',

			'TIME_SPENT_IN_LOGS',
			'TIME_ESTIMATE',
			'MATCH_WORK_TIME',
			'TASK_CONTROL',
			'ALLOW_CHANGE_DEADLINE',
			'ALLOW_TIME_TRACKING',

			Disk\UserField::getMainSysUFCode(),
			CRM\UserField::getMainSysUFCode(),
		];
	}

	/**
	 * @param string|null $workMode
	 * @param int|null $stageId
	 * @param int|null $projectId
	 * @return array
	 */
	public function getFilter(?string $workMode, ?int $stageId, ?int $projectId): array
	{
		$filter = [
			'CHECK_PERMISSIONS' => 'Y',
			'ZOMBIE' => 'N',
		];
		$filter = $this->addProjectToFilter($filter, $projectId);
		$filter = $this->addFlowToFilter($filter, $this->searchParams->flowId);
		$filter = $this->addCreatorToFilter($filter, $this->searchParams->creatorId);
		$filter = $this->addMemberToFilter($filter, $this->searchParams->ownerId, $projectId, $workMode);
		$filter = $this->addSearchStringToFilter($filter);
		$filter = $this->addCounterToFilter($filter, $projectId);
		$filter = $this->applyExtraFilter($filter);
		$filter = $this->applyStagesAdditionalFilter($filter, $workMode, $stageId, $projectId);
		$filter = $this->applyFilterPreset(
			$filter,
			[
				'userId' => $this->searchParams->ownerId,
				'projectId' => ($projectId ?? 0),
				'presetId' => ($this->searchParams->presetId ?? TaskProvider::PRESET_NONE),
			],
		);

		return $filter;
	}

	private function addFlowToFilter(array $filter, ?int $flowId): array
	{
		if ($flowId)
		{
			$filter['FLOW'] = $flowId;
		}

		return $filter;
	}

	private function addCreatorToFilter(array $filter, ?int $creatorId): array
	{
		if ($creatorId)
		{
			$filter['CREATED_BY'] = $creatorId;
		}

		return $filter;
	}

	private function addProjectToFilter(array $filter, ?int $projectId): array
	{
		if ($projectId)
		{
			$filter['GROUP_ID'] = $projectId;
		}

		return $filter;
	}

	private function addMemberToFilter(array $filter, int $member, ?int $projectId, ?string $workMode): array
	{
		if ($projectId && $workMode === StagesTable::WORK_MODE_USER)
		{
			$filter['MEMBER'] = $member;
		}

		return $filter;
	}

	private function addSearchStringToFilter(array $filter): array
	{
		if (isset($this->searchParams->searchString))
		{
			$searchValue = SearchIndex::prepareStringToSearch($this->searchParams->searchString);
			if ($searchValue !== '')
			{
				$filter['::SUBFILTER-FULL_SEARCH_INDEX']['*FULL_SEARCH_INDEX'] = $searchValue;
			}
		}

		return $filter;
	}

	private function addCounterToFilter(array $filter, ?int $projectId): array
	{
		if (
			$this->searchParams->counterId === TaskProvider::COUNTER_EXPIRED
			|| $this->searchParams->counterId === TaskProvider::COUNTER_NEW_COMMENTS
		)
		{
			if ($projectId)
			{
				$filter['MEMBER'] = $this->searchParams->ownerId;
			}
			$filter['IS_MUTED'] = 'N';

			if ($this->searchParams->counterId === TaskProvider::COUNTER_EXPIRED)
			{
				$filter['REAL_STATUS'] = [Status::PENDING, Status::IN_PROGRESS];
				$filter['<=DEADLINE'] = Deadline::getExpiredTime();
			}
			elseif ($this->searchParams->counterId === TaskProvider::COUNTER_NEW_COMMENTS)
			{
				$filter['WITH_NEW_COMMENTS'] = 'Y';
			}
		}

		return $filter;
	}

	private function applyExtraFilter(array $filter): array
	{
		if (empty($this->extra))
		{
			return $filter;
		}

		if (
			!empty($this->extra['filterParams']['ID'])
			&& is_array($this->extra['filterParams']['ID'])
		)
		{
			$filter['ID'] = $this->extra['filterParams']['ID'];
		}

		return $filter;
	}

	private function applyStagesAdditionalFilter(
		array $filter,
		?string $workMode,
		?int $selectedStageId,
		?int $projectId
	): array
	{
		if ($selectedStageId === null)
		{
			return $filter;
		}

		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($workMode);

		$stagesEntityId = ($workMode == StagesTable::WORK_MODE_GROUP ? $projectId : $this->searchParams->ownerId);
		$stages = StagesTable::getStages($stagesEntityId);
		foreach ($stages as $column)
		{
			$stageId = StagesTable::getStageIdByCode($column['ID'], $stagesEntityId);
			if (
				(is_array($stageId) && in_array($selectedStageId, $stageId))
				|| $selectedStageId === (int)$stageId
			)
			{
				if ($column['ADDITIONAL_FILTER'])
				{
					return array_merge(
						$filter,
						$column['ADDITIONAL_FILTER']
					);
				}
				else
				{
					if ($workMode == StagesTable::WORK_MODE_USER)
					{
						return array_merge(
							$filter,
							[
								'STAGES_ID' => $stageId,
							]
						);
					}

					return array_merge(
						$filter,
						[
							'STAGE_ID' => $stageId,
						]
					);
				}
			}
		}
		StagesTable::setWorkMode($prevWorkMode);

		return $filter;
	}

	private function applyFilterPreset(array $filter, array $presetConfig): array
	{
		/** @var Filter $filterInstance */
		$isSprintKanban = (($presetConfig['isSprintKanban'] ?? null) === 'Y');
		if ($isSprintKanban)
		{
			$taskService = new TaskService($presetConfig['userId']);
			$filterInstance = $taskService->getFilterInstance(
				$presetConfig['projectId'],
				($presetConfig['isCompletedSprint'] === 'Y' ? 'complete' : 'active')
			);
		}
		else
		{
			$filterInstance = Filter::getInstance($presetConfig['userId'], $presetConfig['projectId']);
		}

		if (method_exists(Filter::class, 'setRolePresetsEnabledForMobile'))
		{
			Filter::setRolePresetsEnabledForMobile(true);
		}

		$filterValues = [];
		if (array_key_exists($presetConfig['presetId'], $filterInstance->getAllPresets()))
		{
			$filterOptions = $filterInstance->getOptions();
			$filterSettings = (
				$filterOptions->getFilterSettings($presetConfig['presetId'])
				?? $filterOptions->getDefaultPresets()[$presetConfig['presetId']]
			);
			$sourceFields = $filterInstance->getFilters();
			$filterValues = Options::fetchFieldValuesFromFilterSettings($filterSettings, [], $sourceFields);
		}
		$filterInstance->setFilterData($filterValues);

		$filter = array_merge($filter, $filterInstance->process());
		unset($filter['ONLY_ROOT_TASKS']);

		return $filter;
	}

	private function getOrder(?string $workMode): array
	{
		$order = [];

		if (!isset($workMode))
		{
			$order['IS_PINNED'] = 'DESC';
		}

		if ($this->order === TaskProvider::ORDER_DEADLINE)
		{
			$order['DEADLINE'] = 'ASC,NULLS';
		}
		else
		{
			$order['ACTIVITY_DATE'] = 'DESC';
		}

		$order['ID'] = 'DESC';

		return $order;
	}

	private function getParams(?int $projectId): array
	{
		return [
			'MODE' => 'mobile',
			'PUBLIC_MODE' => 'Y',
			'USE_MINIMAL_SELECT_LEGACY' => 'N',
			'RETURN_ACCESS' => 'Y',
			'PROJECT_ID' => ($projectId ?? 0),
			'TARGET_USER_ID' => $this->searchParams->ownerId,
		];
	}

	/**
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @param array $params
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 */
	private function getTasks(
		array $select = [],
		array $filter = [],
		array $order = [],
		array $params = [],
		PageNavigation $pageNavigation = null
	): array
	{
		$getListParams = [
			'select' => $select,
			'legacyFilter' => $filter,
			'order' => $order,
			'group' => [],
			'NAV_PARAMS' => $this->getNavParams($pageNavigation),
		];
		$listResult = Manager\Task::getList($this->userId, $getListParams, $params);

		$tasks = $listResult['DATA'];
		$tasks = $this->fillDataForTasks($tasks);

		return $tasks;
	}

	public function getTask(int $taskId): TaskDto
	{
		$task = $this->getTaskData($taskId);
		$task = $this->fillUserFieldsData([$task], true)[0];

		return $this->prepareItems([$task])[0];
	}

	public function getFullTask(int $taskId, string $workMode, ?int $kanbanOwnerId): array
	{
		$task = $this->getTaskData($taskId, true);
		$task = $this->fillUserFieldsData([$task], true)[0];

		$projectId = !empty($task['GROUP_ID']) ? (int)$task['GROUP_ID'] : 0;
		$relatedTaskIds = $this->getRelatedTaskIds($taskId);
		$allTasks = $this->getTaskHierarchy($relatedTaskIds, $taskId, (int)$task['PARENT_ID']);

		$taskStage = [];
		$kanban = [];
		if ($kanbanOwnerId)
		{
			$taskStageProvider = new TasksStagesProvider($workMode, null, $projectId, $kanbanOwnerId);
			$taskStage = $taskStageProvider->getStages([$taskId => $task]);

			$searchParams = new TaskRequestFilter();
			$searchParams->ownerId = $kanbanOwnerId;

			$stageProvider = new StageProvider($this->userId, $searchParams);
			$kanban = $stageProvider->getKanbanInfoByWorkMode($projectId, $taskId, $workMode)->getData();
		}

		$allTasks[] = $task;

		return [
			'tasks' => $this->prepareItems($allTasks),
			'users' => $this->getUsersData($allTasks),
			'groups' => $this->getGroupsData($allTasks),
			'flows' => $this->getFlowsData($allTasks),
			'relatedTaskIds' => $relatedTaskIds,
			'taskStage' => $taskStage,
			'kanban' => $kanban,
		];
	}

	public function areAllScrumSubtasksCompleted(int $parentTaskId, int $groupId, int $excludeSubtaskId = 0): bool
	{
		if ($parentTaskId === 0 || $groupId === 0)
		{
			return false;
		}

		$group = WorkGroup::getById($groupId);
		$isScrumProject = $group && $group->isScrumProject();
		if (!$isScrumProject)
		{
			return false;
		}

		$query = new TaskQuery($this->userId);
		$query->setSelect([
			'ID',
			'STATUS',
		]);

		if ($excludeSubtaskId > 0)
		{
			$query->setWhere([
				'::LOGIC' => 'AND',
				['=PARENT_ID' => $parentTaskId],
				['!=ID' => $excludeSubtaskId]
			]);
		}
		else
		{
			$query->setWhere([
				['=PARENT_ID' => $parentTaskId],
			]);
		}

		$tasks = (new TaskList())->getList($query);

		foreach ($tasks as $task)
		{
			if ((int)$task['STATUS'] !== Status::COMPLETED)
			{
				return false;
			}
		}

		return true;
	}

	private function getRelatedTaskIds(int $taskId): array
	{
		try
		{
			$relatedTaskIdsResult = CTaskDependence::getList([], ['TASK_ID' => $taskId]);
		}
		catch (\Exception $exception)
		{
			return [];
		}

		$relatedTaskIds = [];
		while ($task = $relatedTaskIdsResult->fetch())
		{
			$relatedTaskIds[] = (int)$task['DEPENDS_ON_ID'];
		}

		(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($this->userId, $relatedTaskIds);

		$preparedRelatedTasks = [];
		foreach ($relatedTaskIds as $relatedTaskId)
		{
			if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $relatedTaskId))
			{
				$preparedRelatedTasks[] = $relatedTaskId;
			}
		}

		return $preparedRelatedTasks;
	}

	// get parent task, sub tasks, related tasks
	private function getTaskHierarchy(array $relatedTaskIds, int $taskId, int $parentId): array
	{
		$select = $this->getSelect();
		$query = new TaskQuery($this->userId);
		$query->setSelect($select);

		$shouldFillDodData = false;
		$taskIds = $relatedTaskIds;
		if ($parentId > 0 && !in_array($parentId, $relatedTaskIds, false))
		{
			$taskIds[] = $parentId;
			$shouldFillDodData = true;
		}

		if (!empty($taskIds))
		{
			$query->setWhere([
				'::LOGIC' => 'OR',
				['=PARENT_ID' => $taskId],
				['ID' => $taskIds],
			]);
		}
		else
		{
			$query->setWhere(['=PARENT_ID' => $taskId]);
		}

		try
		{
			$tasks = (new TaskList())->getList($query);
		}
		catch (TaskListException $exception)
		{
			return [];
		}

		$tasks = array_column($tasks, null, 'ID');

		return $this->fillDataForTasks($tasks, false, $shouldFillDodData);
	}

	private function getTaskData(int $taskId, bool $isFullData = false): array|TaskDto
	{
		$taskItem = new \CTaskItem($taskId, $this->userId);
		$task = $taskItem->getData(false, ['select' => $this->getSelect()]);

		$tasks = [$task['ID'] => $task];
		$tasks = $this->fillDataForTasks($tasks, $isFullData);

		return $tasks[$task['ID']];
	}

	private function fillDataForTasks(array $tasks, bool $isFullData = false, bool $shouldFillDodData = true): array
	{
		$tasks = $this->fillCountersData($tasks);
		$tasks = $this->fillResultData($tasks);
		$tasks = $this->fillTimerData($tasks);
		$tasks = $this->fillStatusData($tasks);
		$tasks = $this->fillTagsData($tasks);
		$tasks = $this->fillCrmData($tasks);
		$tasks = $this->fillDiskFilesData($tasks);
		$tasks = $this->fillFormattedDescription($tasks);
		$tasks = $this->fillUserFieldsData($tasks);
		$tasks = $this->fillActionData($tasks);

		if ($shouldFillDodData)
		{
			$tasks = $this->fillDodData($tasks);
		}

		if ($isFullData)
		{
			$tasks = $this->fillChecklistFullData($tasks);
			// todo: relatedTasks, subTasks, parentTask
		}
		else
		{
			$tasks = $this->fillChecklistCommonData($tasks);
		}

		$tasks = $this->formatDateFieldsForOutput($tasks);

		return $tasks;
	}

	private function getNavParams(PageNavigation $pageNavigation): array
	{
		return [
			'nPageSize' => $pageNavigation->getLimit(),
			'iNumPageSize' => $pageNavigation->getOffset(),
			'iNumPage' => $pageNavigation->getCurrentPage(),
		];
	}

	private function fillCountersData(array $tasks): array
	{
		$counter = new TaskCounter($this->userId);

		foreach ($tasks as $id => $task)
		{
			$counterResult = $counter->getMobileRowCounter($id);
			$counters = $counterResult['counters'];

			$tasks[$id]['COUNTER'] = $counterResult;
			$tasks[$id]['NEW_COMMENTS_COUNT'] = (
			$counters['new_comments']
				?: $counters['muted_new_comments']
				?: $counters['project_new_comments']
			);
		}

		return $tasks;
	}

	private function fillResultData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$taskIds = array_keys($tasks);

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['TASK_REQUIRE_RESULT'] = 'N';
			$tasks[$id]['TASK_HAS_RESULT'] = 'N';
			$tasks[$id]['TASK_HAS_OPEN_RESULT'] = 'N';
			$tasks[$id]['RESULTS_COUNT'] = 0;
		}

		$query = (new \Bitrix\Main\ORM\Query\Query(ResultTable::getEntity()))
			->addSelect('TASK_ID')
			->addSelect(new ExpressionField('LAST_RESULT_ID', 'MAX(%s)', 'ID'))
			->addSelect(new ExpressionField('RESULTS_COUNT', 'COUNT(%s)', 'ID'))
			->whereIn('TASK_ID', $taskIds)
			->addGroup('TASK_ID')
		;
		$lastResults = $query->fetchAll();

		if (!empty($lastResults))
		{
			foreach ($lastResults as $lastResult)
			{
				$tasks[$lastResult['TASK_ID']]['RESULTS_COUNT'] = (int)$lastResult['RESULTS_COUNT'];
			}

			$results = ResultTable::GetList([
				'select' => ['TASK_ID', 'STATUS'],
				'filter' => ['@ID' => array_column($lastResults, 'LAST_RESULT_ID')],
			])->fetchAll();

			foreach ($results as $row)
			{
				$taskId = $row['TASK_ID'];
				$status = (int)$row['STATUS'];
				$tasks[$taskId]['TASK_HAS_RESULT'] = 'Y';
				$tasks[$taskId]['TASK_HAS_OPEN_RESULT'] = ($status === ResultTable::STATUS_OPENED ? 'Y' : 'N');
			}
		}

		$requireResults = ParameterTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'@TASK_ID' => $taskIds,
				'=CODE' => ParameterTable::PARAM_RESULT_REQUIRED,
				'=VALUE' => 'Y',
			],
		])->fetchAll();

		foreach ($requireResults as $row)
		{
			$taskId = $row['TASK_ID'];
			$tasks[$taskId]['TASK_REQUIRE_RESULT'] = 'Y';
		}

		return $tasks;
	}

	private function fillTimerData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$timerManager = \CTaskTimerManager::getInstance($this->userId);
		$runningTaskData = $timerManager->getRunningTask(false);

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = 'N';
			$tasks[$id]['TIMER_RUN_TIME'] = 0;

			if (
				is_array($runningTaskData)
				&& (int)$task['ID'] === (int)$runningTaskData['TASK_ID']
				&& $task['ALLOW_TIME_TRACKING'] === 'Y'
			)
			{
				$tasks[$id]['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = 'Y';
				$tasks[$id]['TIMER_RUN_TIME'] = (int)$runningTaskData['RUN_TIME'];
			}
		}

		return $tasks;
	}

	private function fillStatusData(array $tasks): array
	{
		foreach ($tasks as $id => $task)
		{
			if (array_key_exists('STATUS', $task))
			{
				$tasks[$id]['SUB_STATUS'] = $task['STATUS'];
				$tasks[$id]['STATUS'] = $task['REAL_STATUS'];

				unset($tasks[$id]['REAL_STATUS']);
			}
		}

		return $tasks;
	}

	private function fillChecklistFullData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		return (new ChecklistProvider())->fillFullDataForTasks($tasks);
	}

	private function fillChecklistCommonData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		return (new ChecklistProvider())->fillCommonDataForTasks($tasks);
	}

	private function fillTagsData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$tasks = array_map(function ($task) {
			$task['TAGS'] = [];

			return $task;
		}, $tasks);

		$taskIds = array_keys($tasks);
		foreach ($taskIds as $taskId)
		{
			$tasks[$taskId]['TAGS'] = [];
		}

		$res = LabelTable::getList([
			'select' => [
				'ID',
				'NAME',
				'TASK_ID' => 'TASKS.ID',
			],
			'filter' => [
				'TASK_ID' => $taskIds,
			],
		]);
		while ($row = $res->fetch())
		{
			$tasks[$row['TASK_ID']]['TAGS'][] = [
				'ID' => $row['ID'],
				'NAME' => $row['NAME'],
			];
		}

		return $tasks;
	}

	private function fillCrmData(array $tasks): array
	{
		if (!Loader::includeModule('crm') || empty($tasks))
		{
			return $tasks;
		}

		$ufCrmTaskCode = CRM\UserField::getMainSysUFCode();
		$ufCrmTask = CRM\UserField::getSysUFScheme()[$ufCrmTaskCode];
		$displayField =
			Field::createByType('crm', $ufCrmTaskCode)
				->setIsMultiple($ufCrmTask['MULTIPLE'] === 'Y')
				->setIsUserField(true)
				->setUserFieldParams($ufCrmTask)
				->setContext(Field::MOBILE_CONTEXT)
		;
		$display = new Display(0, [$ufCrmTaskCode => $displayField]);

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['CRM'] = [];

			if (empty($task[$ufCrmTaskCode]) || !is_array($task[$ufCrmTaskCode]))
			{
				continue;
			}

			$items = CRM\Fields\Collection::createFromArray($task[$ufCrmTaskCode])->filter();
			$res =
				$display
					->setItems([[$ufCrmTaskCode => $items->toArray()]])
					->getValues(0)
			;

			if (
				!is_array($res[$ufCrmTaskCode]['config']['entityList'])
				|| count($res[$ufCrmTaskCode]['config']['entityList']) !== $items->count()
			)
			{
				continue;
			}

			$tasks[$id]['CRM'] = array_combine($items->toArray(), $res[$ufCrmTaskCode]['config']['entityList']);
		}

		return $tasks;
	}

	private function fillDiskFilesData(array $tasks): array
	{
		$fileIds = [];
		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['FILES'] = [];
			$fileId = $task[Disk\UserField::getMainSysUFCode()] ?? [];
			if ($fileId !== false)
			{
				$fileIds[] = $fileId;
			}
		}
		$fileIds = array_merge(...$fileIds);
		$fileIds = array_unique($fileIds);

		if (empty($fileIds))
		{
			return $tasks;
		}

		$attachmentsData = (new DiskFileProvider())->getDiskFileAttachments($fileIds);

		foreach ($tasks as $id => $task)
		{
			foreach ($task[Disk\UserField::getMainSysUFCode()] as $fileId)
			{
				if ($attachmentsData[$fileId])
				{
					$tasks[$id]['FILES'][] = $attachmentsData[$fileId];
				}
			}
		}

		return $tasks;
	}

	private function fillFormattedDescription(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['PARSED_DESCRIPTION'] = '';
		}

		if ($textFragmentParserClass = TextFragmentParser::getTextFragmentParserClass())
		{
			$textFragmentParser = new $textFragmentParserClass();

			foreach ($tasks as $id => $task)
			{
				if (!$task['DESCRIPTION'])
				{
					continue;
				}

				$textFragmentParser->setText($task['DESCRIPTION']);
				$textFragmentParser->setFiles(isset($task['FILES']) && $task['FILES'] ? $task['FILES'] : []);

				$tasks[$id]['PARSED_DESCRIPTION'] = htmlspecialchars_decode($textFragmentParser->getParsedText(), ENT_QUOTES);
			}
		}

		return $tasks;
	}

	private function fillUserFieldsData(array $tasks, bool $setIsLoaded = false): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$taskUserFieldProvider = new TaskUserFieldProvider();

		foreach ($tasks as $id => $task)
		{
			$tasks[$id]['USER_FIELDS'] = $taskUserFieldProvider->getUserFields($task);
			$tasks[$id]['USER_FIELD_NAMES'] = array_column($tasks[$id]['USER_FIELDS'], 'FIELD_NAME');
			$tasks[$id]['ARE_USER_FIELDS_LOADED'] = $setIsLoaded;
		}

		return $tasks;
	}

	private function fillActionData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $data)
		{
			$request = [
				ActionDictionary::ACTION_TASK_READ => 'CAN_READ',
				ActionDictionary::ACTION_TASK_EDIT => 'CAN_UPDATE',
				ActionDictionary::ACTION_TASK_DEADLINE => 'CAN_UPDATE_DEADLINE',
				ActionDictionary::ACTION_TASK_CHANGE_DIRECTOR => 'CAN_UPDATE_CREATOR',
				ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE => 'CAN_UPDATE_RESPONSIBLE',
				ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES => 'CAN_UPDATE_ACCOMPLICES',
				ActionDictionary::ACTION_TASK_DELEGATE => 'CAN_DELEGATE',
				ActionDictionary::ACTION_TASK_RATE => 'CAN_UPDATE_MARK',
				ActionDictionary::ACTION_TASK_REMINDER => 'CAN_UPDATE_REMINDER',
				ActionDictionary::ACTION_TASK_ELAPSED_TIME => 'CAN_UPDATE_ELAPSED_TIME',
				ActionDictionary::ACTION_CHECKLIST_ADD => 'CAN_ADD_CHECKLIST',
				ActionDictionary::ACTION_CHECKLIST_EDIT => 'CAN_UPDATE_CHECKLIST',
				ActionDictionary::ACTION_TASK_REMOVE => 'CAN_REMOVE',
				ActionDictionary::ACTION_TASK_TIME_TRACKING => 'CAN_USE_TIMER',
				ActionDictionary::ACTION_TASK_START => 'CAN_START',
				ActionDictionary::ACTION_TASK_PAUSE => 'CAN_PAUSE',
				ActionDictionary::ACTION_TASK_COMPLETE => 'CAN_COMPLETE',
				ActionDictionary::ACTION_TASK_RENEW => 'CAN_RENEW',
				ActionDictionary::ACTION_TASK_APPROVE => 'CAN_APPROVE',
				ActionDictionary::ACTION_TASK_DISAPPROVE => 'CAN_DISAPPROVE',
				ActionDictionary::ACTION_TASK_DEFER => 'CAN_DEFER',
				ActionDictionary::ACTION_TASK_TAKE => 'CAN_TAKE',
			];
			$taskModel = TaskModel::createFromId($id);
			$accessController = new TaskAccessController($this->userId);
			$rights = $accessController->batchCheck(array_map(fn(string $key) => $taskModel, $request), $taskModel);

			$tasks[$id]['ACTION'] = array_combine($request, $rights);
			$tasks[$id]['ACTION_OLD'] = $this->translateAllowedActionNames(
				\CTaskItem::getAllowedActionsArray($this->userId, $data, true)
			);
		}

		return $tasks;
	}

	private function fillDodData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $task)
		{
			if ($task["PARENT_ID"] > 0)
			{
				continue;
			}

			$isDodNecessary = $this->isDodNecessary($task['ID'], $task['GROUP_ID']);
			if ($isDodNecessary)
			{
				$tasks[$id]['DOD'] = $this->getDodTypes($task['ID'], $task['GROUP_ID']);
			}

			$tasks[$id]['DOD']['IS_NECESSARY'] = $isDodNecessary;
		}

		return $tasks;
	}

	private function isDodNecessary(int $taskId, int $groupId): bool
	{
		$userId = User::getId();
		if (!Group::canReadGroupTasks($userId, $groupId))
		{
			return false;
		}

		return (new DefinitionOfDoneService($userId))->isNecessary($groupId, $taskId);
	}

	private function getDodTypes(int $taskId, int $groupId): array
	{
		$definitionOfDoneService = new DefinitionOfDoneService(User::getId());
		$dodTypes = $definitionOfDoneService->getTypes($groupId);
		$itemType = $definitionOfDoneService->getItemType($taskId);
		$activeTypeId = $itemType->isEmpty() ? $dodTypes[0]['id'] : $itemType->getId();

		return [
			'TYPES' => $dodTypes,
			'ACTIVE_TYPE_ID' => (int)$activeTypeId,
		];
	}

	/**
	 * @param array $actions
	 * @return array
	 */
	private function translateAllowedActionNames(array $actions): array
	{
		if (empty($actions))
		{
			return [];
		}

		$translatedActions = [];

		foreach ($actions as $name => $value)
		{
			$translatedActions[str_replace('ACTION_', '', $name)] = $value;
		}

		$replaces = [
			'CHANGE_DIRECTOR' => 'EDIT.ORIGINATOR',
			'CHECKLIST_REORDER_ITEMS' => 'CHECKLIST.REORDER',
			'ELAPSED_TIME_ADD' => 'ELAPSEDTIME.ADD',
			'START_TIME_TRACKING' => 'DAYPLAN.TIMER.TOGGLE',
		];
		foreach ($replaces as $from => $to)
		{
			if (array_key_exists($from, $translatedActions))
			{
				$translatedActions[$to] = $translatedActions[$from];
				unset($translatedActions[$from]);
			}
		}

		$replaces = [
			'CHANGE_DEADLINE' => 'EDIT.PLAN',
			'CHECKLIST_ADD_ITEMS' => 'CHECKLIST.ADD',
			'ADD_FAVORITE' => 'FAVORITE.ADD',
			'DELETE_FAVORITE' => 'FAVORITE.DELETE',
		];
		foreach ($replaces as $from => $to)
		{
			if (array_key_exists($from, $translatedActions))
			{
				$translatedActions[$to] = $translatedActions[$from];
			}
		}

		return $translatedActions;
	}

	private function fillViewsCount(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return $tasks;
		}

		$contentIds = [];
		foreach ($tasks as $id => $data)
		{
			$contentIds[] = 'TASK-' . $id;
		}

		$contentViews = UserContentView::getViewData([
			'contentId' => $contentIds,
		]);

		foreach ($tasks as $id => $data)
		{
			$contentId = 'TASK-' . $id;
			if (!empty($contentViews[$contentId]))
			{
				$tasks[$id]['VIEWS_COUNT'] = (int)$contentViews[$contentId]['CNT'];
			}
			else
			{
				$tasks[$id]['VIEWS_COUNT'] = 0;
			}
		}

		return $tasks;
	}

	public function updateParentIdToTaskIds(
		int $parentId,
		?array $newSubTasks = [],
		?array $deletedSubTasks = []
	): array
	{
		Collection::normalizeArrayValuesByInt($newSubTasks, false);
		Collection::normalizeArrayValuesByInt($deletedSubTasks, false);

		$updatedNewSubTasks = [];
		$updatedDeletedSubTasks = [];

		(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($this->userId, [...$newSubTasks, ...$deletedSubTasks]);

		foreach ($newSubTasks as $taskId)
		{
			$handler = new Task($this->userId);
			try
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
				{
					$result = $handler->update($taskId, ['PARENT_ID' => $parentId]);
					if (!$result)
					{
						throw new TaskUpdateException($handler->getErrors());
					}
					$updatedNewSubTasks[] = (int)$taskId;
				}

			}
			catch (TaskUpdateException $e)
			{

			}
		}

		if (!empty($updatedNewSubTasks))
		{
			$updatedNewSubTasksQuery = (new TaskQuery($this->userId))
				->setSelect($this->getSelect())
				->setWhere(['ID' => $updatedNewSubTasks])
			;

			$updatedNewSubTasksData = $this->getTasksByQuery($updatedNewSubTasksQuery);
		}
		else
		{
			$updatedNewSubTasksData = [];
		}

		foreach ($deletedSubTasks as $taskId)
		{
			$handler = new Task($this->userId);
			try
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
				{
					$result = $handler->update($taskId, ['PARENT_ID' => 0]);
					if (!$result)
					{
						throw new TaskUpdateException($handler->getErrors());
					}
					$updatedDeletedSubTasks[] = (int)$taskId;
				}
			}
			catch (TaskUpdateException $e)
			{
				// Handle exception if needed
			}
		}

		return [
			'updatedNewSubTasks' => $updatedNewSubTasksData,
			'updatedDeletedSubTasks' => $updatedDeletedSubTasks,
		];
	}

	public function updateRelatedTasks(
		int $taskId,
		?array $newRelatedTasks = [],
		?array $deletedRelatedTasks = []
	): array
	{
		Collection::normalizeArrayValuesByInt($newRelatedTasks, false);
		Collection::normalizeArrayValuesByInt($deletedRelatedTasks, false);

		$updatedNewRelatedTasks = [];
		$updatedDeletedRelatedTasks = [];
		$taskDependence = new \CTaskDependence();
		foreach ($newRelatedTasks as $relatedTaskId)
		{
			$relatedTaskId = (int)$relatedTaskId;
			if ($relatedTaskId > 0)
			{
				$result = $taskDependence->add([
					'TASK_ID' => $taskId,
					'DEPENDS_ON_ID' => $relatedTaskId,
				]);
				if ($result !== false)
				{
					$updatedNewRelatedTasks[] = $relatedTaskId;
				}
			}
		}

		$updatedNewRelatedTasksData = [];

		if (!empty($updatedNewRelatedTasks) && is_array($updatedNewRelatedTasks))
		{
			$newTasksQuery = (new TaskQuery($this->userId))
				->setSelect($this->getSelect())
				->setWhere(['ID' => $updatedNewRelatedTasks])
			;
			$updatedNewRelatedTasksData = $this->getTasksByQuery($newTasksQuery);
		}

		foreach ($deletedRelatedTasks as $relatedTaskId)
		{
			$relatedTaskId = (int)$relatedTaskId;
			if ($relatedTaskId > 0)
			{
				$result = $taskDependence->delete($taskId, $relatedTaskId);
				if ($result !== false)
				{
					$updatedDeletedRelatedTasks[] = $relatedTaskId;
				}
			}
		}

		return [
			'updatedNewRelatedTasks' => $updatedNewRelatedTasksData,
			'updatedDeletedRelatedTasks' => $updatedDeletedRelatedTasks,
		];
	}

	/**
	 * @param \Bitrix\Tasks\Provider\TaskQuery $query
	 * @param int|null $projectId
	 * @return array
	 */
	private function getTasksByQuery(TaskQuery $query): array
	{
		try
		{
			$tasks = (new TaskList())->getList($query);
		}
		catch (TaskListException $exception)
		{
			return [];
		}

		return [
			'items' => $this->prepareItems($tasks),
			'users' => $this->getUsersData($tasks),
			'groups' => $this->getGroupsData($tasks),
		];
	}

	/**
	 * Prepares date fields of ISO-8601 format for base suitable format
	 */
	private function formatDateFieldsForInput(array $fields): array
	{
		$getUf = $this->isUfExist(array_keys($fields));
		$dateFields = $this->getDateFields($getUf);

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (
				isset($fields[$fieldName])
				&& ($date = $fields[$fieldName])
				&& is_string($date)
			)
			{
				$timestamp = strtotime($date);
				if ($timestamp !== false)
				{
					$timestamp += \CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
					$fields[$fieldName] = ConvertTimeStamp($timestamp, 'FULL');
				}
			}
		}

		return $fields;
	}

	/**
	 * Prepares date fields for output in ISO-8610 format
	 *
	 * @param array $tasks
	 * @return array
	 */
	private function formatDateFieldsForOutput(array $tasks): array
	{
		if (empty($tasks))
		{
			return $tasks;
		}

		static $dateFields;

		if (!$dateFields)
		{
			$dateFields = $this->getDateFields($this->isUfExist(array_keys(array_values($tasks)[0])));
		}

		$localOffset = (new \DateTime())->getOffset();
		$userOffset = \CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;
		$newOffset = ($offset >= 0 ? '+' : '') . UI::formatTimeAmount($offset, 'HH:MI');

		foreach ($dateFields as $fieldName => $fieldData)
		{
			foreach ($tasks as $id => $task)
			{
				if (
					isset($task[$fieldName])
					&& ($field = $task[$fieldName])
				)
				{
					if (is_array($field))
					{
						foreach ($field as $key => $value)
						{
							if ($date = new DateTime($value))
							{
								$tasks[$id][$fieldName][$key] = mb_substr($date->format('c'), 0, -6) . $newOffset;
							}
						}
					}
					elseif ($date = new DateTime($field))
					{
						$tasks[$id][$fieldName] = mb_substr($date->format('c'), 0, -6) . $newOffset;
					}
				}
			}
		}

		return $tasks;
	}

	private function getDateFields(bool $getUf): array
	{
		return array_filter(
			\CTasks::getFieldsInfo($getUf),
			static fn($item) => ($item['type'] === 'datetime'),
		);
	}

	private function isUfExist(array $fields): bool
	{
		foreach ($fields as $field)
		{
			if (mb_strpos($field, 'UF_') === 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $tasks
	 * @return TaskDto[]
	 */
	private function prepareItems(array $tasks): array
	{
		$prepared = array_map(
			static fn(array $task) => (
				TaskDto::make([
					'id' => $task['ID'],
					'name' => $task['TITLE'],
					'description' => htmlspecialchars_decode($task['DESCRIPTION'], ENT_QUOTES),
					'parsedDescription' => $task['PARSED_DESCRIPTION'],
					'groupId' => $task['GROUP_ID'],
					'flowId' => $task['FLOW_ID'] ?? 0,
					'timeElapsed' => $task['TIME_SPENT_IN_LOGS'] + $task['TIMER_RUN_TIME'],
					'timeEstimate' => $task['TIME_ESTIMATE'],
					'commentsCount' => $task['COMMENTS_COUNT'],
					'serviceCommentsCount' => $task['SERVICE_COMMENTS_COUNT'],
					'newCommentsCount' => $task['NEW_COMMENTS_COUNT'],
					'viewsCount' => $task['VIEWS_COUNT'] ?? 0,
					'resultsCount' => $task['RESULTS_COUNT'],
					'parentId' => $task['PARENT_ID'] ?? 0,

					'status' => $task['STATUS'],
					'subStatus' => $task['SUB_STATUS'],
					'priority' => $task['PRIORITY'],
					'mark' => $task['MARK'] ?? null,

					'creator' => $task['CREATED_BY'],
					'responsible' => $task['RESPONSIBLE_ID'],
					'accomplices' => is_array($task['ACCOMPLICES'])
						? array_map('intval', $task['ACCOMPLICES'])
						: [],
					'auditors' => is_array($task['AUDITORS'])
						? array_map('intval', $task['AUDITORS'])
						: [],

					// 'relatedTasks' => $task['RELATED_TASKS'] ?? [],
					// 'subTasks' => $task['SUB_TASKS'] ?? [],

					'crm' => $task['CRM'] ?? [],
					'tags' => $task['TAGS'] ?? [],
					'files' => $task['FILES'] ?? [],

					'isMuted' => ($task['IS_MUTED'] === 'Y'),
					'isPinned' => ($task['IS_PINNED'] === 'Y'),
					'isInFavorites' => ($task['FAVORITE'] === 'Y'),
					'isResultRequired' => ($task['TASK_REQUIRE_RESULT'] === 'Y'),
					'isResultExists' => ($task['TASK_HAS_RESULT'] === 'Y'),
					'isOpenResultExists' => ($task['TASK_HAS_OPEN_RESULT'] === 'Y'),
					'isMatchWorkTime' => ($task['MATCH_WORK_TIME'] === 'Y'),
					'allowChangeDeadline' => ($task['ALLOW_CHANGE_DEADLINE'] === 'Y'),
					'allowTimeTracking' => ($task['ALLOW_TIME_TRACKING'] === 'Y'),
					'allowTaskControl' => ($task['TASK_CONTROL'] === 'Y'),
					'isTimerRunningForCurrentUser' => ($task['TIMER_IS_RUNNING_FOR_CURRENT_USER'] === 'Y'),

					'deadline' => strtotime($task['DEADLINE']) ?: null,
					'activityDate' => strtotime($task['ACTIVITY_DATE']) ?: null,
					'startDatePlan' => strtotime($task['START_DATE_PLAN']) ?: null,
					'endDatePlan' => strtotime($task['END_DATE_PLAN']) ?: null,
					'startDate' => strtotime($task['DATE_START']) ?: null,
					'endDate' => strtotime($task['CLOSED_DATE']) ?: null,

					'checklist' => $task['CHECKLIST'] ?? null,
					'checklistDetails' => $task['CHECKLIST_DETAILS'] ?? null,
					'counter' => $task['COUNTER'] ?? null,

					'actions' => $task['ACTION'] ?? [],
					'actionsOld' => $task['ACTION_OLD'] ?? [],

					'isDodNecessary' => $task['DOD']['IS_NECESSARY'],
					'dodTypes' => $task['DOD']['TYPES'] ?? [],
					'activeDodTypeId' => $task['DOD']['ACTIVE_TYPE_ID'] ?? [],

					'areUserFieldsLoaded' => $task['ARE_USER_FIELDS_LOADED'] ?? false,
					'userFields' => $task['USER_FIELDS'] ?? [],
					'userFieldNames' => $task['USER_FIELD_NAMES'] ?? [],
				])
			),
			$tasks,
		);

		return array_values($prepared);
	}

	private function getUsersData(array $tasks): array
	{
		$userIds = [];

		foreach ($tasks as $task)
		{
			$userIds[] = [
				($task['CREATED_BY'] ?? 0),
				($task['RESPONSIBLE_ID'] ?? 0),
				...($task['ACCOMPLICES'] ?? []),
				...($task['AUDITORS'] ?? []),
			];
		}

		if (!empty($userIds))
		{
			$userIds = array_merge(...$userIds);
		}

		$userIds[] = $this->userId;
		$userIds[] = $this->searchParams->ownerId;

		return UserRepository::getByIds($userIds);
	}

	private function getGroupsData(array $tasks, ?int $projectId = null): array
	{
		if (!empty($tasks))
		{
			$groupIds = array_column($tasks, 'GROUP_ID');

			return GroupProvider::loadByIds($groupIds);
		}

		if ($projectId && SocialNetwork\Group::canReadGroupTasks($this->userId, $projectId))
		{
			return GroupProvider::loadByIds([$projectId]);
		}

		return [];
	}

	private function getFlowsData(array $tasks): array
	{
		$flowIds = array_column($tasks, 'FLOW_ID');
		$flowIds = array_unique(array_filter(array_map('intval', $flowIds)));
		if (empty($flowIds))
		{
			return [];
		}

		return (new FlowProvider($this->userId))->getFlowsById($flowIds);
	}

	private function filterAllowedFields(array $fields): array
	{
		return array_intersect_key(
			$fields,
			array_flip([
				'TITLE',
				'DESCRIPTION',
				'DEADLINE',
				'GROUP_ID',
				'FLOW_ID',
				'PARENT_ID',
				'PRIORITY',
				'CREATED_BY',
				'RESPONSIBLE_ID',
				'ACCOMPLICES',
				'AUDITORS',
				Disk\UserField::getMainSysUFCode(),
				'UPLOADED_FILES',
				'STAGE_ID',
				'CRM',
				'START_DATE_PLAN',
				'END_DATE_PLAN',
				'TIME_ESTIMATE',
				'TAGS',
				'MARK',
				'MATCH_WORK_TIME',
				'ALLOW_CHANGE_DEADLINE',
				'ALLOW_TIME_TRACKING',
				'TASK_CONTROL',
				'SE_PARAMETER',
				'CHECKLIST',
				'IM_CHAT_ID',
				'IM_MESSAGE_ID',
				'USER_FIELDS',
			]),
		);
	}

	private function processFiles(array $fields = [], int $taskId = 0): array
	{
		$filesUfCode = Disk\UserField::getMainSysUFCode();

		if (!isset($fields[$filesUfCode]) || !is_array($fields[$filesUfCode]))
		{
			return $fields;
		}

		$prevFileIds = TaskObject::wakeUpObject(['ID' => $taskId])->fillUtsData()?->getUfTaskWebdavFiles() ?? [];
		if (!is_array($prevFileIds))
		{
			$prevFileIds = [];
		}

		$prevFilesCount = count($prevFileIds);

		$nextFileIds = $fields[$filesUfCode];
		$nextFilesCount = count($nextFileIds);

		// process removed files
		if ($nextFilesCount < $prevFilesCount)
		{
			return $fields;
		}

		// process new disk files
		foreach ($nextFileIds as $nextFileId)
		{
			if (str_starts_with($nextFileId, 'n'))
			{
				return $fields;
			}
		}

		// files attached via uploader will be processed in background
		unset($fields[$filesUfCode]);

		return $fields;
	}

	private function processUploadedFiles(array $fields = [], int $taskId = 0): array
	{
		$filesUfCode = Disk\UserField::getMainSysUFCode();

		if (isset($fields[$filesUfCode]) && $fields[$filesUfCode] === '')
		{
			$fields[$filesUfCode] = [];
		}

		if (empty($fields['UPLOADED_FILES']) || !is_array($fields['UPLOADED_FILES']))
		{
			unset($fields['UPLOADED_FILES']);

			return $fields;
		}

		if (!is_array($fields[$filesUfCode]))
		{
			$fields[$filesUfCode] = [];
		}

		$controller = new TaskController(['taskId' => $taskId]);
		$uploader = new Uploader($controller);
		$pendingFiles = $uploader->getPendingFiles($fields['UPLOADED_FILES']);

		foreach ($pendingFiles->getFileIds() as $fileId)
		{
			$addingResult = Disk::addFile($fileId);
			if ($addingResult->isSuccess())
			{
				$fields[$filesUfCode][] = $addingResult->getData()['ATTACHMENT_ID'];
			}
		}

		$pendingFiles->makePersistent();

		return $fields;
	}

	private function processCrmElements(array $fields): array
	{
		if (
			!array_key_exists('CRM', $fields)
			|| !Loader::includeModule('crm')
		)
		{
			return $fields;
		}

		$crmUfCode = CRM\UserField::getMainSysUFCode();
		if (!is_array($fields[$crmUfCode] ?? null))
		{
			$fields[$crmUfCode] = [];
		}

		if (!is_array($fields['CRM']) || empty($fields['CRM']))
		{
			return $fields;
		}

		foreach ($fields['CRM'] as $item)
		{
			$entityTypeName = $item['type'];
			$entityId = $item['id'];

			if ($entityTypeName === DynamicMultipleProvider::DYNAMIC_MULTIPLE_ID)
			{
				[$entityTypeId, $entityId] = DynamicMultipleProvider::parseId($entityId);
				$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);
			}
			else
			{
				$entityTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($entityTypeName);
			}

			if ($entityTypeAbbr)
			{
				$fields[$crmUfCode][] = "{$entityTypeAbbr}_{$entityId}";
			}
		}

		return $fields;
	}

	private function processUserFields(array $fields): array
	{
		if (!is_array($fields['USER_FIELDS'] ?? null))
		{
			return $fields;
		}

		foreach ($fields['USER_FIELDS'] as $name => $data)
		{
			if (!str_starts_with($name, 'UF_AUTO_'))
			{
				continue;
			}

			$value = $data['value'];

			if (Type::tryFrom($data['type']) === Type::DateTime)
			{
				$isMultiple = is_array($value);
				$value = ($isMultiple ? $value : [$value]);

				foreach ($value as $key => $val)
				{
					if ($val === '')
					{
						continue;
					}

					$timestamp = (int)$val;
					$timestamp += \CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();

					$value[$key] = ConvertTimeStamp($timestamp, 'FULL');
				}

				$value = ($isMultiple ? $value : $value[0]);
			}

			$fields[$name] = $value;
		}

		unset($fields['USER_FIELDS']);

		return $fields;
	}

	private function processScenario(array $fields): array
	{
		$fields['SCENARIO_NAME'] = [ScenarioTable::SCENARIO_MOBILE];

		if (!empty($fields[CRM\UserField::getMainSysUFCode()]))
		{
			$fields['SCENARIO_NAME'][] = ScenarioTable::SCENARIO_CRM;
		}

		return $fields;
	}

	private function updateStage(\CTaskItem $task, int $stageId): void
	{
		$taskId = $task->getId();

		if (!$taskId || !$stageId)
		{
			return;
		}

		$stage = StagesTable::getList([
			'select' => ['ID', 'ENTITY_ID', 'ENTITY_TYPE'],
			'filter' => ['ID' => $stageId],
		])->fetch();

		if (!$stage)
		{
			return;
		}

		if ($stage['ENTITY_TYPE'] === StagesTable::WORK_MODE_GROUP)
		{
			$task->update(['STAGE_ID' => $stageId]);
		}
		else if ($stage['ENTITY_TYPE'] === StagesTable::WORK_MODE_USER)
		{
			$taskStage = TaskStageTable::getList([
				'filter' => [
					'TASK_ID' => $taskId,
					'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
					'STAGE.ENTITY_ID' => $stage['ENTITY_ID'],
				],
			]);
			try
			{
				while ($row = $taskStage->fetch())
				{
					if ((int)$row['STAGE_ID'] !== $stageId)
					{
						TaskStageTable::update($row['ID'], ['STAGE_ID' => $stageId]);
					}
				}
			}
			catch (\Exception $exception)
			{
				LogFacade::logThrowable($exception);
			}
		}
	}

	/**
	 * @param array $fields
	 * @return int
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function add(array $fields): int
	{
		$fields = $this->filterAllowedFields($fields);
		if ($this->hasFlowTaskCreationRestrictions($fields))
		{
			throw new TaskAddException('Tariff restrictions', 1);
		}
		$fields = $this->formatDateFieldsForInput($fields);
		$fields = $this->processUploadedFiles($fields);
		$fields = $this->processCrmElements($fields);
		$fields = $this->processUserFields($fields);
		$fields = $this->processScenario($fields);
		$fields = $this->processMembers($fields);

		$stageId = ($fields['STAGE_ID'] ?? 0);
		unset($fields['STAGE_ID']);

		$task = \CTaskItem::add($fields, $this->userId, ['CLONE_DISK_FILE_ATTACHMENT' => true]);

		$this->updateStage($task, $stageId);
		$this->saveChecklist($task, $fields);

		return $task->getId();
	}

	private function hasFlowTaskCreationRestrictions(array $fields): bool
	{
		$flowId = (int)($fields['FLOW_ID'] ?? 0);
		if (!$flowId)
		{
			return false;
		}

		return TariffPlanRestrictionProvider::isFlowRestricted();
	}

	private function processMembers(array $fields): array
	{
		$auditors = array_map('intval', $fields['AUDITORS'] ?? []);
		$accomplices = array_map('intval', $fields['ACCOMPLICES'] ?? []);

		$checklistItems = $fields['CHECKLIST'] ?? [];
		$findByRole = fn(array $members, string $role) => array_keys(
			array_filter($members, fn($member) => $member['TYPE'] === $role)
		);

		foreach ($checklistItems as $item)
		{
			$members = $item['MEMBERS'] ?? [];
			$auditors = array_merge(
				$auditors,
				$findByRole($members, RoleDictionary::ROLE_AUDITOR)
			);
			$accomplices = array_merge(
				$accomplices,
				$findByRole($members, RoleDictionary::ROLE_ACCOMPLICE)
			);
		}

		$fields['AUDITORS'] = array_unique($auditors);
		$fields['ACCOMPLICES'] = array_unique($accomplices);

		return $fields;
	}

	private function saveChecklist(\CTaskItem $task, array $fields): void
	{
		$taskId = $task->getId();
		if ($taskId && is_array($fields['CHECKLIST']))
		{
			$userId = CurrentUser::get()->getId();
			$items = $fields['CHECKLIST'];

			if (!TaskAccessController::can($userId, ActionDictionary::ACTION_CHECKLIST_SAVE, $taskId, $items))
			{
				return;
			}

			foreach ($items as $id => $item)
			{
				$item['ID'] = ((int)($item['ID'] ?? null) === 0 ? null : (int)$item['ID']);

				$item['IS_COMPLETE'] = (
					($item['IS_COMPLETE'] === true)
					|| ((int)$item['IS_COMPLETE'] > 0)
				);
				$item['IS_IMPORTANT'] = (
					($item['IS_IMPORTANT'] === true)
					|| ((int)$item['IS_IMPORTANT'] > 0)
				);

				$items[$item['NODE_ID']] = $item;

				unset($items[$id]);
			}

			TaskCheckListFacade::merge($taskId, $this->userId, $items, [
				'context' => CheckListFacade::TASK_ADD_CONTEXT,
			]);
		}
	}

	/**
	 * @param int $taskId
	 * @param array $fields
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function update(int $taskId, array $fields): bool
	{
		$fields = $this->filterAllowedFields($fields);
		$fields = $this->formatDateFieldsForInput($fields);
		$fields = $this->processUploadedFiles($fields, $taskId);
		$fields = $this->processFiles($fields, $taskId);
		$fields = $this->processCrmElements($fields);
		$fields = $this->processUserFields($fields);

		if (!empty($fields))
		{
			$task = new \CTaskItem($taskId, $this->userId);
			$task->update($fields);
		}

		return true;
	}

	/**
	 * @param int $taskId
	 * @param string $fileId
	 * @return ?DiskFileDto
	 * @throws CTaskAssertException
	 * @throws TaskUpdateException
	 */
	public function attachUploadedFiles(int $taskId, string $fileId): ?DiskFileDto
	{
		$prevFileIds = TaskObject::wakeUpObject(['ID' => $taskId])->fillUtsData()?->getUfTaskWebdavFiles() ?? [];
		$fields = [
			'UF_TASK_WEBDAV_FILES' => $prevFileIds,
			'UPLOADED_FILES' => [$fileId],
		];

		$fields = $this->processUploadedFiles($fields, $taskId);
		$handler = new Task($this->userId);
		try
		{
			$result = $handler->update($taskId, $fields);
		}
		catch (TaskUpdateException $e)
		{
			return null;
		}

		if ($result === false)
		{
			return null;
		}

		$nextFileIds = TaskObject::wakeUpObject(['ID' => $taskId])->fillUtsData()?->getUfTaskWebdavFiles();
		$diffFiles = array_diff($nextFileIds, $prevFileIds);
		$newFileId = reset($diffFiles);

		if (!empty($newFileId))
		{
			$diskAttachment = (new DiskFileProvider())->getDiskFileAttachments([$newFileId]);

			if (!empty($diskAttachment[$newFileId]))
			{
				return DiskFileDto::make($diskAttachment[$newFileId]);
			}

		}

		return null;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function remove(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->delete();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function follow(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->startWatch();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function unfollow(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->stopWatch();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 */
	public function startTimer(int $taskId): bool
	{
		$timer = \CTaskTimerManager::getInstance($this->userId);

		return ($timer->start($taskId) !== false);
	}

	/**
	 * @param int $taskId
	 * @return bool
	 */
	public function pauseTimer(int $taskId): bool
	{
		$timer = \CTaskTimerManager::getInstance($this->userId);

		return ($timer->stop($taskId) !== false);
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function start(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->startExecution();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function take(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->takeExecution();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function pause(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->pauseExecution();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function complete(int $taskId): bool
	{
		$creator = TaskObject::wakeUpObject(['ID' => $taskId])->fillCreator();
		if (!$creator)
		{
			return false;
		}

		if (
			$creator->getId() === $this->userId
			|| UserModel::createFromId($this->userId)->isAdmin()
			|| !ResultManager::requireResult($taskId)
			|| (
				($lastResult = ResultManager::getLastResult($taskId))
				&& (int)$lastResult['STATUS'] === ResultTable::STATUS_OPENED
			)
		)
		{
			$task = new \CTaskItem($taskId, $this->userId);
			$task->complete();

			return true;
		}

		return false;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function renew(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->renew();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function defer(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->defer();

		return true;
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function delegate(int $taskId, int $userId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->delegate($userId);

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function approve(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->approve();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function disapprove(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$task->disapprove();

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws CTaskAssertException
	 * @throws TasksException
	 */
	public function ping(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		$taskData = $task->getData(false);

		if ($taskData)
		{
			$commentPoster = CommentPoster::getInstance($taskId, $this->userId);
			$commentPoster && $commentPoster->postCommentsOnTaskStatusPinged($taskData);

			\CTaskNotifications::sendPingStatusMessage($taskData, $this->userId);

			return true;
		}

		return false;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function pin(int $taskId): bool
	{
		$result = UserOption::add($taskId, $this->userId, UserOption\Option::PINNED);

		return $result->isSuccess();
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function unpin(int $taskId): bool
	{
		$result = UserOption::delete($taskId, $this->userId, UserOption\Option::PINNED);

		return $result->isSuccess();
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function mute(int $taskId): bool
	{
		$result = UserOption::add($taskId, $this->userId, UserOption\Option::MUTED);

		return $result->isSuccess();
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function unmute(int $taskId): bool
	{
		$result = UserOption::delete($taskId, $this->userId, UserOption\Option::MUTED);

		return $result->isSuccess();
	}

	public function read(int $taskId): bool
	{
		$task = new \CTaskItem($taskId, $this->userId);
		if ($task->checkCanRead())
		{
			ViewedTable::set($taskId, $this->userId);

			return true;
		}

		return false;
	}
}
