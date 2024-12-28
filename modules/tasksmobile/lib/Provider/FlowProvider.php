<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Tasks\Flow\Filter\Filter;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Grid\Preload\AccessPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AtWorkTaskPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AverageAtWorkTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AverageCompletedTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\AveragePendingTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\CompletedTaskPreloader;
use Bitrix\Tasks\Flow\Grid\Preload\PendingTaskPreloader;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Path\FlowPathMaker;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Template\CounterStyle;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasksmobile\Dto\FlowDto;
use Bitrix\TasksMobile\Dto\FlowRequestFilter;
use Bitrix\Tasks\Flow\Search;
use Bitrix\TasksMobile\FlowAiAdvice\Provider\FlowAiAdviceProvider;
use CPullWatch;

final class FlowProvider
{
	public const COUNTER_NONE = 'none';
	public const COUNTER_EXPIRED = 'expired';
	public const COUNTER_NEW_COMMENTS = 'new_comments';

	public const PUSH_COMMAND_FLOW_ADDED = 'flow_add';
	public const PUSH_COMMAND_FLOW_UPDATED = 'flow_update';
	public const PUSH_COMMAND_FLOW_DELETED = 'flow_delete';

	public const PRESET_NONE = 'none';

	public const ORDER_ACTIVITY = 'ACTIVITY';

	private int $userId;
	private string $order;
	private ?PageNavigation $pageNavigation;
	private ?FlowRequestFilter $searchParams;

	/**
	 * @param int $userId
	 * @param string $order
	 * @param array $extra
	 * @param FlowRequestFilter|null $searchParams
	 * @param PageNavigation|null $pageNavigation
	 */
	function __construct(
		int $userId,
		string $order = FlowProvider::ORDER_ACTIVITY,
		array $extra = [],
		?FlowRequestFilter $searchParams = null,
		?PageNavigation $pageNavigation = null,
	)
	{
		$this->userId = $userId;
		$this->searchParams = ($searchParams ?? new FlowRequestFilter());
		$this->order = $order;
		$this->extra = $extra;
		$this->pageNavigation = $pageNavigation;
	}

	public function subscribeCurrentUserToPull(): bool
	{
		$success = true;
		if (Loader::includeModule('pull'))
		{
			$pushTagList = [
				FlowProvider::PUSH_COMMAND_FLOW_ADDED,
				FlowProvider::PUSH_COMMAND_FLOW_UPDATED,
				FlowProvider::PUSH_COMMAND_FLOW_DELETED,
			];

			foreach ($pushTagList as $tag)
			{
				$success = $success && CPullWatch::add($this->userId, $tag);
			}
		}
		else
		{
			$success = false;
		}

		return $success;
	}

	public function getFlowById(int $id): ?FlowDto
	{
		$flows = $this->getFlowsById([$id]);

		return $flows[0];
	}

	/**
	 * @param int[] $ids
	 * @return FlowDto[]
	 */
	public function getFlowsById(array $ids = []): array
	{
		Collection::normalizeArrayValuesByInt($ids, false);

		if (empty($ids))
		{
			return [];
		}

		$query = (new FlowQuery($this->userId))
			->setSelect($this->getAvailableFields())
			->setWhere((new ConditionTree())->whereIn('ID', $ids))
		;

		$data = $this->getFlowsInternal($query);

		return $data['items'];
	}

	public function getFlows(): array
	{
		$query = (new FlowQuery($this->userId))
			->setSelect($this->getAvailableFields())
			->setWhere($this->getFlowFilter())
			->setPageNavigation($this->pageNavigation)
			->setOrderBy(['ACTIVITY' => 'DESC', 'ID' => 'DESC'])
		;

		$data = $this->getFlowsInternal($query);

		return [
			'items' => $data['items'],
			'users' => UserRepository::getByIds($data['userIds']),
			'groups' => GroupProvider::loadByIds($data['groupIds']),
			'showFlowsInfo' => FlowProvider::getShowFlowsFeatureInfo(),
		];
	}

	public function getShowFlowsFeatureInfo(): bool
	{
		return \CUserOptions::getOption('show_flows_feature_info', 'enabled', true);
	}

	public function disableShowFlowsFeatureInfoFlag(): bool
	{
		return \CUserOptions::setOption('show_flows_feature_info', 'enabled', false);
	}

	private function getIdsFromUsersArray(array $users): array
	{
		$ids = [];

		foreach ($users as $user)
		{
			$ids[] = $user->id;
		}

		return $ids;
	}

	private function getFlowsInternal(FlowQuery $query): array
	{
		// ToDo check flow read permissions and allowance to create tasks in the flow

		$flowCollection = $this->getMainFlowProvider()->getList($query);
		$flowCollectionTasksCount = $this->getMainFlowProvider()->getFlowTasksCount(
			$this->userId,
			[Status::PENDING, Status::IN_PROGRESS, Status::SUPPOSEDLY_COMPLETED, Status::DEFERRED],
			...$flowCollection->getIdList(),
		);

		$flowAiAdviceProvider = new FlowAiAdviceProvider();
		if ($flowAiAdviceProvider->isFlowAiAdviceAvailable())
		{
			$flowTaskProvider = new \Bitrix\Tasks\Flow\Provider\TaskProvider();
			$tasksTotal = $flowTaskProvider->getTotalTasks($flowCollection->getIdList());

			$flowsAiAdvices = $flowAiAdviceProvider->getFlowsAiAdvices($flowCollection->getIdList(), $tasksTotal);
		}

		$this->preloadFlowData($flowCollection);

		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');

		$items = [];
		$userIds = [];
		$groupIds = [];

		foreach ($flowCollection as $flow)
		{
			$flowId = $flow->getId();

			$flow->setOptions($this->getOptionProvider()->getOptions($flowId));
			$flowData = $flow->toArray();

			$ownerId = $flow->getOwnerId();
			$creatorId = $flow->getCreatorId();
			$pendingUserIds = $this->getIdsFromUsersArray($this->getPendingTaskPreloader()->get($flowId));
			$atWorkUserIds = $this->getIdsFromUsersArray($this->getAtWorkTaskPreloader()->get($flowId));
			$completedUserIds = $this->getIdsFromUsersArray($this->getCompletedTaskPreloader()->get($flowId));
			$averagePendingTime = $this->getAveragePendingTimePreloader()->get($flowId)->getFormatted();
			$averageAtWorkTime = $this->getAverageAtWorkTimePreloader()->get($flowId)->getFormatted();
			$averageCompletedTime = $this->getAverageCompletedTimePreloader()->get($flowId)->getFormatted();
			$plannedCompletionTime = $flow->getPlannedCompletionTime();
			$plannedCompletionTimeText = DatePresenter::createFromSeconds($plannedCompletionTime)->getFormatted();

			$userIds[] = [
				$ownerId,
				$creatorId,
				...$pendingUserIds,
				...$atWorkUserIds,
				...$completedUserIds,
			];

			$groupId = $flow->getGroupId();
			$groupIds[] = $groupId;

			$taskCreators = $memberFacade->getTaskCreatorAccessCodes($flowId);

			$taskAssigneeAccessCodes = $memberFacade->getTeamAccessCodes($flowId);
			$taskAssignees = (new AccessCodeConverter(...$taskAssigneeAccessCodes))->getUserIds();

			$enableFlowUrl = null;
			$flowActive = $flow->isActive();
			if (!$flowActive)
			{
				$pathMakerInstance = new FlowPathMaker(ownerId: $this->userId);
				$enableFlowUrl = $pathMakerInstance->addQueryParam('demo_flow', $flowId)->makeEntitiesListPath();
			}

			$items[] = new FlowDto(
				id: $flowId,
				ownerId: $ownerId,
				creatorId: $creatorId,
				groupId: $groupId,
				templateId: $flow->getTemplateId(),
				efficiency: $this->getMainFlowProvider()->getEfficiency($flow),
				active: $flowActive,
				demo: $flow->isDemo(),
				plannedCompletionTime: $plannedCompletionTime,
				name: $flow->getName(),
				distributionType: $flow->getDistributionType(),
				taskCreators: $taskCreators,
				taskAssignees: $taskAssignees,
				pending: $pendingUserIds,
				atWork: $atWorkUserIds,
				completed: $completedUserIds,
				tasksTotal: $tasksTotal[$flowId] ?? null,
				myTasksTotal: $flowCollectionTasksCount[$flowId] ?? 0,
				myTasksCounter: $this->getFlowCounters($flow, $this->userId),
				averagePendingTime: $averagePendingTime,
				averageAtWorkTime: $averageAtWorkTime,
				averageCompletedTime: $averageCompletedTime,
				plannedCompletionTimeText: $plannedCompletionTimeText,
				enableFlowUrl: $enableFlowUrl,
				activity: $flowData['activity']?->getTimestamp() ?? 0, // ToDo no getters $flow->getActivity(),
				description: $flowData['description'] ?? null,
				responsibleCanChangeDeadline: $flowData['responsibleCanChangeDeadline'] ?? null,
				matchWorkTime: $flowData['matchWorkTime'] ?? null,
				notifyAtHalfTime: $flowData['notifyAtHalfTime'] ?? null,
				notifyOnQueueOverflow: $flowData['notifyOnQueueOverflow'] ?? null,
				notifyOnTasksInProgressOverflow: $flowData['notifyOnTasksInProgressOverflow'] ?? null,
				notifyWhenEfficiencyDecreases: $flowData['notifyWhenEfficiencyDecreases'] ?? null,
				aiAdvice: $flowsAiAdvices[$flowId] ?? null,
			);
		}

		$userIds = array_merge([], ...$userIds);

		return [
			'items' => $items,
			'userIds' => $userIds,
			'groupIds' => $groupIds,
		];
	}

	private function getFlowFilter(): ConditionTree
	{
		$filter = new ConditionTree();
		$filter = $this->addCounterToFilter($filter, $this->searchParams->counterId);
		$filter = $this->addSearchStringToFilter($filter, $this->searchParams->searchString);
		$filter = $this->addExcludedFlowToFilter($filter, $this->searchParams->excludedFlowId);
		$filter = $this->addConditionsForSimilarFlowsToFilter($filter, $this->searchParams->creatorId);
		$filter = $this->addPresetToFilter($filter, $this->searchParams->presetId);
		$filter = $this->addExtraToFilter($filter);

		return $filter;
	}

	/**
	 * @throws ArgumentException
	 */
	private function addPresetToFilter(ConditionTree $filter, string $presetId): ConditionTree
	{
		if ($presetId === FlowProvider::PRESET_NONE)
		{
			return $filter;
		}

		$filterInstance = Filter::getInstance($this->userId);
		$allPresets = $filterInstance->getAvailablePresets();
		if (empty($allPresets[$presetId]))
		{
			return $filter;
		}

		switch ($presetId)
		{
			case Filter::MY_PRESET:
				$condition = new ConditionTree();
				$condition->whereIn('CREATOR_ID', [$this->userId]);
				$condition->whereIn('OWNER_ID', [$this->userId]);
				$condition->logic('or');
				$filter->addCondition($condition);
				break;
			case Filter::ACTIVE_PRESET:
				$filter->where('ACTIVE', 1);
				break;
		}

		return $filter;
	}

	private function addExcludedFlowToFilter(ConditionTree $filter, int $excludedFlowId): ConditionTree
	{
		if ($excludedFlowId === 0)
		{
			return $filter;
		}

		$filter->whereNot('ID', $excludedFlowId);

		return $filter;
	}

	private function addConditionsForSimilarFlowsToFilter(ConditionTree $filter, int $creatorId): ConditionTree
	{
		if ($creatorId === 0)
		{
			return $filter;
		}

		$filter->whereIn('CREATOR_ID', [$creatorId]);
		$filter->where('ACTIVE', 1);

		return $filter;
	}

	private function addExtraToFilter(ConditionTree $filter): ConditionTree
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
			$filter->whereIn('ID', $this->extra['filterParams']['ID']);
		}

		return $filter;
	}

	private function addSearchStringToFilter(ConditionTree $filter, string $searchString = ''): ConditionTree
	{
		if (!empty($searchString))
		{
			$sqlExpression = (new Search\FullTextSearch())->find($searchString);

			if ($sqlExpression)
			{
				$filter->whereIn('ID', $sqlExpression);
			}
		}

		return $filter;
	}

	private function addCounterToFilter(ConditionTree $filter, string $counterId): ConditionTree
	{
		if (!empty($counterId) && $counterId !== FlowProvider::COUNTER_NONE)
		{
			$ids = [];

			$flowRawCounters = Counter::getInstance($this->userId)
				->getRawCounters(Counter\CounterDictionary::META_PROP_FLOW)
			;

			$types = match($counterId)
			{
				FlowProvider::COUNTER_EXPIRED => Counter\CounterDictionary::MAP_FLOW_TOTAL[Counter\CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED],
				FlowProvider::COUNTER_NEW_COMMENTS => Counter\CounterDictionary::MAP_FLOW_TOTAL[Counter\CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS],
				default => 'none',
			};

			$counters = [];

			foreach ($types as $type)
			{
				$typeCounters = $flowRawCounters[$type] ?? [];
				foreach ($typeCounters as $flowId => $value)
				{
					$newValue = isset($counters[$flowId]) ? $counters[$flowId] + $value : $value;
					$counters[$flowId] = $newValue;
				}
			}

			foreach ($counters as $flowId => $counter)
			{
				if ($flowId)
				{
					$ids[] = (int)$flowId;
				}
			}

			if (empty($ids))
			{
				$filter->where('ID', 0);
			}
			else
			{
				$filter->whereIn('ID', $ids);
			}
		}

		return $filter;
	}

	private function getMainFlowProvider(): \Bitrix\Tasks\Flow\Provider\FlowProvider
	{
		static $flowProvider = null;

		if ($flowProvider === null)
		{
			$flowProvider = new \Bitrix\Tasks\Flow\Provider\FlowProvider();
		}

		return $flowProvider;
	}

	private function getAvailableFields(): array
	{
		// ToDo check with fields from real table
		return [
			'ID',
			'OWNER_ID',
			'NAME',
			'EFFICIENCY',
			'CREATOR_ID',
			'GROUP_ID',
			'TEMPLATE_ID',
			'ACTIVE',
			'DEMO',
			'PLANNED_COMPLETION_TIME',
			'ACTIVITY',
			'DESCRIPTION',
			'DISTRIBUTION_TYPE',
			'DEMO',
		];
	}

	private function preloadFlowData(FlowCollection $flowCollection): void
	{
		$flowCollectionIds = $flowCollection->getIdList();
		(new AccessPreloader())->preload(...$flowCollectionIds);
		$this->getPendingTaskPreloader()->preload(...$flowCollectionIds);
		$this->getAtWorkTaskPreloader()->preload(...$flowCollectionIds);
		$this->getCompletedTaskPreloader()->preload(...$flowCollectionIds);
		$this->getAveragePendingTimePreloader()->preload(...$flowCollectionIds);
		$this->getAverageCompletedTimePreloader()->preload(...$flowCollectionIds);
		$this->getAverageAtWorkTimePreloader()->preload(...$flowCollectionIds);

		// (new TeamPreloader())->preload($flowCollection);
		// (new ProjectPreloader())->preload(...$flowCollection->getGroupIdList());
	}

	private function getAverageAtWorkTimePreloader(): AverageAtWorkTimePreloader
	{
		static $averageAtWorkTimePreloader = null;

		if ($averageAtWorkTimePreloader === null)
		{
			$averageAtWorkTimePreloader = new AverageAtWorkTimePreloader();
		}

		return $averageAtWorkTimePreloader;
	}

	private function getAverageCompletedTimePreloader(): AverageCompletedTimePreloader
	{
		static $averageCompletedTimePreloader = null;

		if ($averageCompletedTimePreloader === null)
		{
			$averageCompletedTimePreloader = new AverageCompletedTimePreloader();
		}

		return $averageCompletedTimePreloader;
	}

	private function getAveragePendingTimePreloader(): AveragePendingTimePreloader
	{
		static $averagePendingTimePreloader = null;

		if ($averagePendingTimePreloader === null)
		{
			$averagePendingTimePreloader = new AveragePendingTimePreloader();
		}

		return $averagePendingTimePreloader;
	}

	private function getPendingTaskPreloader(): PendingTaskPreloader
	{
		static $pendingTaskPreloader = null;

		if ($pendingTaskPreloader === null)
		{
			$pendingTaskPreloader = new PendingTaskPreloader();
		}

		return $pendingTaskPreloader;
	}

	private function getAtWorkTaskPreloader(): AtWorkTaskPreloader
	{
		static $atWorkTaskPreloader = null;

		if ($atWorkTaskPreloader === null)
		{
			$atWorkTaskPreloader = new AtWorkTaskPreloader();
		}

		return $atWorkTaskPreloader;
	}

	private function getCompletedTaskPreloader(): CompletedTaskPreloader
	{
		static $completedTaskPreloader = null;

		if ($completedTaskPreloader === null)
		{
			$completedTaskPreloader = new CompletedTaskPreloader();
		}

		return $completedTaskPreloader;
	}

	private function getOptionProvider(): OptionService
	{
		static $optionProvider = null;

		if ($optionProvider === null)
		{
			$optionProvider = OptionService::getInstance();
		}

		return $optionProvider;
	}

	/**
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFlowCounters(\Bitrix\Tasks\Flow\Flow $flow, int $userId): array
	{
		$counter = Counter::getInstance($userId);

		return [
			CounterStyle::STYLE_GREEN => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS, $flow->getId()),
			CounterStyle::STYLE_RED => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED, $flow->getId()),
		];
	}

	public function getTotalCounters(): array
	{
		$counter = Counter::getInstance($this->userId);

		return [
			'flow_total' => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL),
			'flow_total_comments' => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS),
			'flow_total_expired' => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED),
		];
	}

	public function getSearchBarPresets(): array
	{
		$presets = [];
		$filterInstance = Filter::getInstance($this->userId);
		$availablePresets = $filterInstance->getAvailablePresets();

		foreach ($availablePresets as $key => $name) {
			$presets[$key] = [
				'name' => $name,
				'default' => false,
			];
		}

		return $presets;
	}


}
