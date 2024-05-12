<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\TasksMobile\TextFragmentParser;
use Bitrix\Tasks\Internals\Counter\Deadline;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\CheckListTreeTable;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\TasksMobile\Dto\TaskDto;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;

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
	function __construct(
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
			'groups' => $this->getGroupsData($tasks, $params),
			'tasks_stages' => $this->getStagesData($tasks, $workMode, $stageId, $projectId),
		];
	}

	private function getStagesData(array $tasks, ?string $workMode = null, ?int $stageId = null, ?int $projectId = null): array
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

			Disk\UserField::getMainSysUFCode(),
			'UF_CRM_TASK',
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

		$tasks = $this->fillCountersData($tasks);
		$tasks = $this->fillResultData($tasks);
		$tasks = $this->fillTimerData($tasks);
		$tasks = $this->fillStatusData($tasks);
		$tasks = $this->fillChecklistData($tasks);
		$tasks = $this->fillTagsData($tasks);
		$tasks = $this->fillCrmData($tasks);
		$tasks = $this->fillDiskFilesData($tasks);
		$tasks = $this->fillFormattedDescription($tasks);
		$tasks = $this->formatDateFieldsForOutput($tasks);

		return $tasks;
	}

	public function getTask(int $taskId): array|TaskDto
	{
		$taskItem = new \CTaskItem($taskId, $this->userId);
		$task = $taskItem->getData(false, ['select' => $this->getSelect()]);
		$tasks = [$task['ID'] => $task];

		$tasks = $this->fillCountersData($tasks);
		$tasks = $this->fillResultData($tasks);
		$tasks = $this->fillTimerData($tasks);
		$tasks = $this->fillStatusData($tasks);
		$tasks = $this->fillChecklistData($tasks);
		$tasks = $this->fillTagsData($tasks);
		$tasks = $this->fillCrmData($tasks);
		$tasks = $this->fillDiskFilesData($tasks);
		$tasks = $this->fillFormattedDescription($tasks);
		$tasks = $this->fillActionData($tasks);
		$tasks = $this->formatDateFieldsForOutput($tasks);

		// todo: relatedTasks, subTasks, parentTask

		return $this->prepareItems($tasks)[0];
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
		}

		$query = (new \Bitrix\Main\ORM\Query\Query(ResultTable::getEntity()))
			->addSelect('TASK_ID')
			->addSelect(new ExpressionField('RES_ID', 'MAX(%s)', 'ID'))
			->whereIn('TASK_ID', $taskIds)
			->addGroup('TASK_ID')
		;
		$lastResults = $query->fetchAll();

		if (!empty($lastResults))
		{
			$lastResults = array_column($lastResults, 'RES_ID');
			$results = ResultTable::GetList([
				'select' => ['TASK_ID', 'STATUS'],
				'filter' => ['@ID' => $lastResults],
			])->fetchAll();

			foreach ($results as $row)
			{
				$taskId = $row['TASK_ID'];
				$tasks[$taskId]['TASK_HAS_RESULT'] = 'Y';

				if ((int)$row['STATUS'] === ResultTable::STATUS_OPENED)
				{
					$tasks[$taskId]['TASK_HAS_OPEN_RESULT'] = 'Y';
				}
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

			if (
				is_array($runningTaskData)
				&& (int)$task['ID'] === (int)$runningTaskData['TASK_ID']
				&& $task['ALLOW_TIME_TRACKING'] === 'Y'
			)
			{
				$tasks[$id]['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = 'Y';
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

	private function fillChecklistData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$taskIds = array_keys($tasks);

		foreach ($taskIds as $taskId)
		{
			$tasks[$taskId]['CHECKLIST'] = [
				'COMPLETED' => 0,
				'UNCOMPLETED' => 0,
			];
		}

		$query = new Query(CheckListTable::getEntity());
		$query
			->setSelect(['TASK_ID', 'IS_COMPLETE', new ExpressionField('CNT', 'COUNT(TASK_ID)')])
			->setFilter(['TASK_ID' => $taskIds])
			->setGroup(['TASK_ID', 'IS_COMPLETE'])
			->registerRuntimeField(
				'',
				new ReferenceField(
					'IT',
					CheckListTreeTable::class,
					Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
					['join_type' => 'INNER']
				)
			)
		;
		$result = $query->exec();
		while ($row = $result->fetch())
		{
			$completedKey = ($row['IS_COMPLETE'] == 'Y' ? 'COMPLETED' : 'UNCOMPLETED');
			$tasks[$row['TASK_ID']]['CHECKLIST'][$completedKey] = (int)$row['CNT'];
		}

		return $tasks;
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

			if (!is_array($task[$ufCrmTaskCode]) || empty($task[$ufCrmTaskCode]))
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

		$attachmentsData = Disk::getAttachmentData($fileIds);
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

	private function fillActionData(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		foreach ($tasks as $id => $data)
		{
			$tasks[$id]['ACTION'] = $this->translateAllowedActionNames(
				\CTaskItem::getAllowedActionsArray($this->userId, $data, true)
			);
		}

		return $tasks;
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
			static fn ($item) => ($item['type'] === 'datetime'),
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
				'description' => $task['DESCRIPTION'],
				'parsedDescription' => $task['PARSED_DESCRIPTION'],
				'groupId' => $task['GROUP_ID'],
				'timeElapsed' => $task['TIME_SPENT_IN_LOGS'],
				'timeEstimate' => $task['TIME_ESTIMATE'],
				'commentsCount' => $task['COMMENTS_COUNT'],
				'serviceCommentsCount' => $task['SERVICE_COMMENTS_COUNT'],
				'newCommentsCount' => $task['NEW_COMMENTS_COUNT'],
				'parentId' => $task['PARENT_ID'] ?? 0,

				'status' => $task['STATUS'],
				'subStatus' => $task['SUB_STATUS'],
				'priority' => $task['PRIORITY'],
				'mark' => $task['MARK'] ?? null,

				'creator' => $task['CREATED_BY'],
				'responsible' => $task['RESPONSIBLE_ID'],
				'accomplices' => array_map('intval', $task['ACCOMPLICES']),
				'auditors' => array_map('intval', $task['AUDITORS']),

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
				'counter' => $task['COUNTER'] ?? null,

				'actions' => $task['ACTION'] ?? [],
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

	private function getGroupsData(array $tasks, array $params = []): array
	{
		static $groupsData = [];

		$groupIds = array_column($tasks, 'GROUP_ID');
		$groupIds[] = $params['PROJECT_ID'];
		$groupIds = array_unique(array_filter(array_map('intval', $groupIds)));
		$groupIds = array_diff($groupIds, array_keys($groupsData));

		if (empty($groupIds))
		{
			return $groupsData;
		}

		$avatarTypes = (Loader::includeModule('socialnetwork') ? Workgroup::getAvatarTypes() : []);
		$newGroupsData = SocialNetwork\Group::getData($groupIds, ['IMAGE_ID', 'AVATAR_TYPE'], $params);
		foreach ($newGroupsData as $id => $group)
		{
			$imageUrl = '';
			if (
				(int)$group['IMAGE_ID'] > 0
				&& is_array($file = \CFile::GetFileArray($group['IMAGE_ID']))
			)
			{
				$imageUrl = $file['SRC'];
			}
			elseif (
				!empty($group['AVATAR_TYPE'])
				&& isset($avatarTypes[$group['AVATAR_TYPE']])
			)
			{
				$imageUrl = $avatarTypes[$group['AVATAR_TYPE']]['mobileUrl'];
			}

			$groupsData[$id] = [
				'ID' => $group['ID'],
				'NAME' => $group['NAME'],
				'IMAGE' => $imageUrl,
				'ADDITIONAL_DATA' => ($group['ADDITIONAL_DATA'] ?? []),
			];
		}

		return array_values($groupsData);
	}

	/**
	 * @param int $taskId
	 * @param array $fields
	 * @return bool
	 * @throws \CTaskAssertException
	 * @throws \TasksException
	 */
	public function update(int $taskId, array $fields): bool
	{
		$fields = $this->formatDateFieldsForInput($fields);

		$task = new \CTaskItem($taskId, $this->userId);
		$task->update($fields);

		return true;
	}

	/**
	 * @param int $taskId
	 * @return bool
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws \CTaskAssertException
	 * @throws \TasksException
	 */
	public function complete(int $taskId): bool
	{
		if (
			!ResultManager::requireResult($taskId)
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
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
	 * @throws \CTaskAssertException
	 * @throws \TasksException
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
