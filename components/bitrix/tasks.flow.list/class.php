<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

require_once(__DIR__ . '/filtervalues.php');

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Errorable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Access\SimpleFlowAccessController;
use Bitrix\Tasks\Flow\Control\Command;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotDeletedException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Grid\Preload\TotalTasksCountPreloader;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionDictionary;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionRepository;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Filter\Filter;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Grid\Action;
use Bitrix\Tasks\Flow\Grid\Actions;
use Bitrix\Tasks\Flow\Grid\Columns;
use Bitrix\Tasks\Flow\Grid\Grid;
use Bitrix\Tasks\Flow\Grid\Preload\AccessPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AtWorkTaskPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AverageAtWorkTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AverageCompletedTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AveragePendingTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\CompletedTaskPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\EfficiencyPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\PendingTaskPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\ProjectPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\TasksCountPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\UserPreloader;
use Bitrix\Tasks\Flow\Grid\Row;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\Tasks\Flow\Provider\TaskProvider;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\Integration\Extranet\User;

final class TasksFlowListComponent extends CBitrixComponent implements Controllerable, Errorable
{
	public const GRID_ID = 'tasks-flow-list';

	private ErrorCollection $errorCollection;
	private CMain $application;
	private ?FlowProvider $flowProvider = null;
	private ?Filter $filter = null;
	private ?FlowUserOptionRepository $flowUserOptionRepository = null;

	private int $userId;
	private int $currentPage = 1;

	public function __construct($component = null)
	{
		parent::__construct($component);

		global $APPLICATION;
		$this->application = $APPLICATION;

		$this->errorCollection = new ErrorCollection();
		$this->userId = CurrentUser::get()->getId();
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): ?Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws ProviderException
	 * @throws InvalidCommandException
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws FlowNotDeletedException
	 */
	public function executeComponent(): void
	{
		if (!FlowFeature::isOptionEnabled())
		{
			ShowError('Disabled');

			return;
		}

		$this->setTitle();
		$this->includeRequiredModules();

		if (!FlowFeature::isOn())
		{
			$this->arResult['isToolAvailable'] = false;
			$this->includeComponentTemplate();

			return;
		}

		$demoService = new \Bitrix\Tasks\Flow\Demo\Service(\Bitrix\Tasks\Util\User::getAdminId());
		if (!$demoService->isDemoFlowsCreated())
		{
			$demoService->createDemoFlows();
		}

		$this->subscribeCurrentUserToPull();

		$this->prepareUrls();

		$this->arResult['isGridRequest'] = false;

		$this->processGridAction();

		$grid = new Grid(self::GRID_ID);

		Columns::build();

		$navigation = new PageNavigation('tasks-flow-navigation');
		$navigation
			->allowAllRecords(false)
			->setPageSize($grid->getPageSize())
			->setCurrentPage($this->currentPage)
			->setPageSizes([
				5,
				10,
				20,
			])
			->initFromUri()
		;

		$this->flowProvider = new FlowProvider();
		$this->filter = Filter::getInstance($this->userId);
		$this->flowUserOptionRepository = FlowUserOptionRepository::getInstance();

		$this->arResult['gridId'] = self::GRID_ID;
		$this->arResult['columns'] = $this->prepareColumns();
		[$this->arResult['rows'], $this->arResult['totalRowsCount']] = $this->prepareRows(
			$grid,
			$navigation,
		);

		$navigation->setRecordCount($this->arResult['totalRowsCount']);

		$this->arResult['navigation'] = $navigation;
		$this->arResult['currentPage'] = $navigation->getCurrentPage();
		$this->arResult['stub'] = $this->getStub();
		$this->arResult['pageSizes'] = [
			[
				'NAME' => '5',
				'VALUE' => '5',
			],
			[
				'NAME' => '10',
				'VALUE' => '10',
			],
			[
				'NAME' => '20',
				'VALUE' => '20',
			],
		];
		$this->prepareFilterParams();
		$this->prepareAccessParams();

		$this->arResult['currentUserId'] = $this->userId;
		$this->arResult['isAhaShownOnMyTasksColumn'] = $this->isAhaShownOnMyTasksColumn();
		$this->arResult['isAhaShownCopilotAdvice'] = $this->isAhaShownCopilotAdvice();
		$this->arResult['isFeatureEnabled'] = FlowFeature::isFeatureEnabled();
		$this->arResult['isFeatureTrialable'] = FlowFeature::isFeatureEnabledByTrial();
		$this->arResult['canTurnOnTrial'] = FlowFeature::canTurnOnTrial();

		$this->arResult['guidePhotoClass'] = $this->getGuidePhotoClass();

		$this->sendAnalytics();

		$this->includeComponentTemplate();
	}

	/**
	 * @throws SystemException
	 */
	public function getMapIdsAction(array $taskIds): array
	{
		$this->includeRequiredModules();

		$taskProvider = new TaskProvider();

		$userId = CurrentUser::get()->getId();

		$map = [];
		foreach ($taskProvider->getMapIds($taskIds) as $taskId => $flowId)
		{
			if (FlowAccessController::can($userId, FlowAction::READ, $flowId))
			{
				$map[$taskId] = $flowId;
			}
		}

		return $map;
	}

	private function setTitle(): void
	{
		if ($this->arParams['SET_TITLE'])
		{
			$title = Loc::getMessage('TASKS_FLOW_LIST_TITLE');

			$this->application->setTitle($title);
		}
	}

	/**
	 * @throws SystemException
	 */
	private function includeRequiredModules(): void
	{
		try
		{
			if (
				!Loader::includeModule('tasks')
				|| !Loader::includeModule('socialnetwork')
			)
			{
				throw new SystemException('Cannot connect required modules');
			}
		}
		catch (LoaderException)
		{
			throw new SystemException('Cannot connect required modules');
		}
	}

	private function subscribeCurrentUserToPull(): void
	{
		if (Loader::includeModule('pull'))
		{
			$pushTagList = [
				PushCommand::FLOW_ADDED,
				PushCommand::FLOW_UPDATED,
				PushCommand::FLOW_DELETED,
			];

			foreach ($pushTagList as $tag)
			{
				CPullWatch::add($this->userId, $tag);
			}
		}
	}

	private function prepareColumns(): array
	{
		$columns = [];

		foreach (Columns::getAll() as $column)
		{
			$columns[] = $column->toArray();
		}

		return $columns;
	}

	/**
	 * @throws ProviderException
	 */
	private function prepareRows(Grid $grid, PageNavigation $navigation): array
	{
		$rows = [];

		$this->fillPinnedIds();

		$select = $this->prepareSelect($grid);

		$flows = $this->getPinnedFlows($select, $navigation);

		$remainingLimit = $navigation->getLimit() - count($flows);
		if ($remainingLimit > 0)
		{
			$unpinnedFlows = $this->completeFlowPage($select, $navigation, $remainingLimit);

			foreach ($unpinnedFlows as $flow)
			{
				$flows->add($flow);
			}
		}

		$totalRowsCount = $this->getTotalFlowCount();

		$this->preloadGridData($flows);

		Actions::build();

		foreach ($flows as $flow)
		{
			$accessController = $flow->getAccessController($this->userId);

			$data = $this->prepareData($flow, $grid);
			$columns = [];
			$editable = $accessController->canUpdate();
			$actions = $this->prepareActions($flow, $accessController);
			$counters = $this->prepareCounters($flow);

			$row = new Row(
				$flow->getId(),
				$data,
				$columns,
				$editable,
				$actions,
				$flow->isActive(),
				$this->isFlowPinnedForUser($flow->getId()),
				$counters,
			);

			$rows[] = $row->toArray();
		}

		return [$rows, $totalRowsCount];
	}

	private function getTotalFlowCount()
	{
		$provider = new FlowProvider();

		$query = new FlowQuery($this->userId);
		$query
			->setAccessCheck(true)
			->setWhere($this->prepareFilter())
		;

		return $provider->getCount($query);
	}

	private function isFlowPinnedForUser(int $flowId)
	{
		return  in_array($flowId, $this->arResult['pinnedIds'], true);
	}

	private function getPinnedFlows($select, $navigation)
	{
		return $this->getFlows(
			$select,
			fn($filter) => $filter->whereIn('ID', $this->arResult['pinnedIds']),
			$navigation->getLimit(),
			$navigation->getOffset()
		);
	}

	private function completeFlowPage($select, $navigation, $remainingLimit = 0)
	{
		$offset = max(0, $navigation->getOffset() - count($this->arResult['pinnedIds']));

		return $this->getFlows(
			$select,
			fn($filter) => $filter->whereNotIn('ID', $this->arResult['pinnedIds']),
			$remainingLimit > 0 ? $remainingLimit : $navigation->getLimit(),
			$offset
		);
	}


	private function getFlows($select, $filterCondition, $limit = 0, $offset = 0)
	{
		$provider = new FlowProvider();

		$flowQuery = new FlowQuery($this->userId);
		$filter = $this->prepareFilter();
		$filterCondition($filter);

		$flowQuery
			->setSelect($select)
			->setWhere($filter)
			->setLimit($limit)
			->setOffset($offset)
			->setOrderBy(['ACTIVITY' => 'DESC', 'ID' => 'DESC']);

		return $provider->getList($flowQuery);
	}

	private function fillPinnedIds(): void
	{
		try
		{
			$options = $this->flowUserOptionRepository->getOptions(
				[
					'USER_ID' => $this->userId,
					'NAME' => FlowUserOptionDictionary::FLOW_PINNED_FOR_USER->value,
					'VALUE' => 'Y',
				]
			);

			$this->arResult['pinnedIds'] = array_map(static fn ($option) => $option->getFlowId(), $options);
		}
		catch (Throwable $e)
		{
			$this->arResult['pinnedIds'] = [];
		}
	}

	private function getStub(): array
	{
		if ($this->filter->isUserFilterApplied())
		{
			return [
				'title' => Loc::getMessage('TASKS_FLOW_LIST_STUB_NO_DATA_TITLE'),
				'description' => Loc::getMessage('TASKS_FLOW_LIST_STUB_NO_DATA_DESCRIPTION'),
			];
		}

		if (User::isExtranet($this->userId))
		{
			return [
				'title' => Loc::getMessage('TASKS_FLOW_LIST_STUB_DESCRIPTION'),
				'description' => '',
			];
		}

		return [
			'title' => Loc::getMessage('TASKS_FLOW_LIST_STUB_TITLE'),
			'description' => Loc::getMessage('TASKS_FLOW_LIST_STUB_DESCRIPTION'),
		];
	}

	private function prepareFilter(): ConditionTree
	{
		return (new FilterValues($this->userId))->prepareFilter($this->filter->getCurrentFilterValues());
	}

	private function prepareSelect(Grid $grid): array
	{
		$listAvailableFields = array_keys($this->flowProvider->getFlowFields());

		$requiredFields = [
			'ID',
			'TEMPLATE_ID',
			'GROUP_ID',
			'PLANNED_COMPLETION_TIME',
			'DISTRIBUTION_TYPE',
			'ACTIVE',
			'DEMO',
		];

		$select = array_filter($listAvailableFields, static function ($fieldId) use ($grid, $requiredFields) {
			return (
				in_array($fieldId, $requiredFields, true)
				|| (
					Columns::has($fieldId)
					&& $grid->isColumnVisible(Columns::get($fieldId))
				)
			);
		});

		return array_values($select);
	}

	private function prepareData(Flow $flow, Grid $grid): array
	{
		$data = [];

		foreach (Columns::getAll() as $column)
		{
			if ($grid->isColumnVisible($column))
			{
				$data[$column->getId()] = $column->prepareData(
					$flow,
					[
						'userId' => $this->userId,
						'isPinned' => $this->isFlowPinnedForUser($flow->getId())
					],
				);
			}
		}

		return $data;
	}

	private function prepareActions(
		Flow $flow,
		SimpleFlowAccessController $accessController,
	): array
	{
		$actions = [];

		foreach (Actions::getAll() as $action)
		{
			if (
				$action->getId() === Action\Edit::ID
				&& !$accessController->canUpdate()
			)
			{
				continue;
			}

			if (
				$action->getId() === Action\Activate::ID
				&& !$accessController->canUpdate()
			)
			{
				continue;
			}

			if (
				$action->getId() === Action\Remove::ID
				&& !$accessController->canDelete()
			)
			{
				continue;
			}

			if (
				$action->getId() === Action\Pin::ID
				&& !$accessController->canRead()
			)
			{
				continue;
			}

			$action->prepareData($flow, ['isPinned' => $this->isFlowPinnedForUser($flow->getId())]);

			$actions[] = $action->toArray();
		}

		return $actions;
	}

	private function prepareCounters(Flow $flow): array
	{
		$counters = [];

		foreach (Columns::getAll() as $column)
		{
			if ($column->hasCounter())
			{
				$counters[$column->getId()] = $column->getCounter($flow, $this->userId);
			}
		}

		return $counters;
	}

	private function prepareUrls(): void
	{
		$this->arResult['pathToGroupTasks'] = $this->arParams['PATH_TO_GROUP_TASKS'];

		$this->arResult['pathToUserTasksTask'] = CComponentEngine::makePathFromTemplate(
			$this->arParams['PATH_TO_USER_TASKS_TASK'],
			['user_id' => $this->userId]
		);

		$this->arResult['pathToUserTasks'] = CComponentEngine::makePathFromTemplate(
			$this->arParams['PATH_TO_USER_TASKS'],
			['user_id' => $this->userId]
		);

		$this->arResult['pathToFlows'] = CComponentEngine::makePathFromTemplate(
			RouteDictionary::PATH_TO_FLOWS,
			['user_id' => $this->userId]
		);
	}

	private function prepareFilterParams(): void
	{
		$this->arResult['filterId'] = $this->filter->getId();
		$this->arResult['filter'] = $this->filter->getFieldArrays();
		$this->arResult['presets'] = $this->filter->getPresets();
	}

	private function prepareAccessParams(): void
	{
		$this->arResult['canCreateFlow'] = FlowAccessController::can($this->userId, FlowAction::CREATE);
	}

	private function isAhaShownOnMyTasksColumn(): bool
	{
		return CUserOptions::getOption(
			'ui-tour',
			'view_date_my_tasks_' . $this->userId,
			null
		) !== null;
	}

	private function isAhaShownCopilotAdvice(): bool
	{
		return Loader::includeModule('ai') && CUserOptions::getOption(
			'ui-tour',
			'view_date_flow_copilot_advice',
			null
		) !== null;
	}

	/**
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 * @throws ProviderException
	 * @throws FlowNotDeletedException
	 */
	private function processGridAction(): void
	{
		$request = Context::getCurrent()?->getRequest();

		try
		{
			if (
				$request?->isPost()
				&& check_bitrix_sessid()
				&& \Bitrix\Main\Grid\Context::isInternalRequest()
				&& $request?->get('grid_id') === self::GRID_ID
			)
			{
				$this->doAction();

				$this->arResult['isGridRequest'] = true;
			}
		}
		catch (\Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException|FlowNotFoundException)
		{
		}
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws InvalidCommandException
	 * @throws ProviderException
	 * @throws FlowNotDeletedException
	 */
	private function doAction(): void
	{
		$request = Context::getCurrent()?->getRequest();

		if ($request?->getPost('action') === \Bitrix\Main\Grid\Actions::GRID_UPDATE_ROW)
		{
			$this->doUpdate();

			$this->currentPage = (int) ($request?->getPost('data')['currentPage'] ?? 1);
		}
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws InvalidCommandException
	 * @throws ProviderException
	 * @throws FlowNotDeletedException
	 */
	private function doUpdate(): void
	{
		$request = Context::getCurrent()?->getRequest();

		$flowProvider = new FlowProvider();
		$flowService = new FlowService($this->userId);

		$flowId = (int)$request?->getPost('id');
		$flow = $flowProvider->getFlow($flowId);
		$accessController = $flow->getAccessController($this->userId);

		$action = $request?->getPost('data')['action'] ?? null;

		if (
			$action === Action\Remove::ID
			&& $accessController->canDelete()
		)
		{
			$deleteCommand = (new Command\DeleteCommand())->setId($flow->getId());
			$flowService->delete($deleteCommand);
		}
	}

	private function preloadGridData(FlowCollection $flowCollection): void
	{
		(new AccessPreloader())->preload(...$flowCollection->getIdList());

		(new EfficiencyPreloader())->preload(...$flowCollection->getIdList());

		(new PendingTaskPreloader())->preload(...$flowCollection->getIdList());
		(new CompletedTaskPreloader())->preload(...$flowCollection->getIdList());
		(new AtWorkTaskPreloader())->preload(...$flowCollection->getIdList());

		(new AveragePendingTimePreloader())->preload(...$flowCollection->getIdList());
		(new AverageAtWorkTimePreloader())->preload(...$flowCollection->getIdList());
		(new AverageCompletedTimePreloader())->preload(...$flowCollection->getIdList());

		(new TasksCountPreloader())->preload($this->userId, ...$flowCollection->getIdList());
		(new TotalTasksCountPreloader())->preload(...$flowCollection->getIdList());

		(new UserPreloader())->preload(...$flowCollection->getOwnerIdList());
		(new ProjectPreloader())->preload($this->userId, ...$flowCollection->getGroupIdList());
	}

	private function sendAnalytics(): void
	{
		$request = Context::getCurrent()?->getRequest();

		if (!empty($request->get('ta_sec')))
		{
			\Bitrix\Tasks\Helper\Analytics::getInstance($this->userId)->onFlowsView(
				$request->get('ta_sec'),
				$request->get('ta_el'),
				$request->get('ta_sub'),
				['p1' => $request->get('p1')]
			);
		}
	}

	private function getLanguagePrefix(): string
	{
		if (Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::getLicensePrefix();
		}

		return LANGUAGE_ID;
	}

	private function getGuidePhotoClass(): string
	{
		$langPrefix = $this->getLanguagePrefix();

		return in_array($langPrefix, ['ru', 'by', 'kz'], true) ? '' : '--en';
	}
}
