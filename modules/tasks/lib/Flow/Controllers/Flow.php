<?php

namespace Bitrix\Tasks\Flow\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\EntitySelector\Converter;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\DeleteCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Decorator\EmptyOwnerDecorator;
use Bitrix\Tasks\Flow\Control\Decorator\ProjectMembersProxyDecorator;
use Bitrix\Tasks\Flow\Control\Decorator\ProjectProxyDecorator;
use Bitrix\Tasks\Flow\Integration\HumanResources\DepartmentService;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionRepository;
use Bitrix\Tasks\Flow\Option\FlowUserOption\FlowUserOptionService;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\Exception\AutoCreationException;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueProvider;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Helper\Analytics;
use InvalidArgumentException;
use Throwable;


use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\Tasks\Flow\Filter\Filter;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class Flow extends Controller
{
	use MessageTrait;
	use ControllerTrait;

	protected FlowService $service;
	protected FlowProvider $provider;
	protected ResponsibleQueueProvider $queueProvider;
	protected OptionService $optionProvider;
	protected FlowUserOptionService $flowUserOptionService;
	protected FlowUserOptionRepository $flowUserOptionRepository;
	protected FlowMemberFacade $memberFacade;
	protected Converter $converter;
	protected int $userId;
	protected Filter $filter;

	public function configureActions(): array
	{
		return [
			'getFeatureParams' => [
				'+prefilters' => [new Scope(Scope::AJAX)]
			],
		];
	}

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->service = new FlowService($this->userId);
		$this->provider = new FlowProvider();
		$this->queueProvider = new ResponsibleQueueProvider();
		$this->optionProvider = OptionService::getInstance();
		$this->memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$this->flowUserOptionService = FlowUserOptionService::getInstance();
		$this->flowUserOptionRepository = FlowUserOptionRepository::getInstance();
		$this->converter = new Converter();
		$this->filter = Filter::getInstance($this->userId);
	}

	/**
	 * @restMethod tasks.flow.flow.get
	 */
	public function getAction(int $flowId): ?\Bitrix\Tasks\Flow\Flow
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$flow = $this->provider->getFlow($flowId, ['*']);

			$responsibleList = $this->converter::convertFromFinderCodes(
				$this->memberFacade->getResponsibleAccessCodes($flowId)
			);

			$taskCreators = $this->converter::convertFromFinderCodes(
				$this->memberFacade->getTaskCreatorAccessCodes($flowId)
			);

			$team = $this->converter::convertFromFinderCodes(
				$this->memberFacade->getTeamAccessCodes($flowId)
			);

			$options = $this->optionProvider->getOptions($flowId);
		}
		catch (FlowNotFoundException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		$flow
			->setResponsibleList($responsibleList)
			->setTaskCreators($taskCreators)
			->setTeam($team)
			->setOptions($options);

		return $flow;
	}

	/**
	 * @restMethod tasks.flow.flow.create
	 */
	public function createAction(FlowDto $flowData, array $analyticsParams = []): ?\Bitrix\Tasks\Flow\Flow
	{
		$trialFeatureEnabled = false;

		if (!FlowFeature::isFeatureEnabled())
		{
			if (FlowFeature::canTurnOnTrial())
			{
				FlowFeature::turnOnTrial();

				$trialFeatureEnabled = true;
			}
			else
			{
				return $this->buildErrorResponse($this->getAccessDeniedError());
			}
		}

		if (
			!FlowAccessController::can(
				$this->userId,
				FlowAction::SAVE,
				null,
				FlowModel::createFromArray($flowData)
			)
		)
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$flowData
			->setCreatorId($this->userId)
			->setActive(true)
			->setActivity();

		$addCommand = AddCommand::createFromArray($flowData)
			->setTaskCreators($this->converter::convertToFinderCodes($flowData->taskCreators ?? []))
			->setResponsibleList($this->converter::convertToFinderCodes($flowData->responsibleList ?? []));

		try
		{
			$service =
				new EmptyOwnerDecorator(
					new ProjectProxyDecorator(
						new ProjectMembersProxyDecorator(
							$this->service
						)
					)
				);

			$flow = $service->add($addCommand);

			$flow->setTrialFeatureEnabled($trialFeatureEnabled);
		}
		catch (AutoCreationException|InvalidCommandException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		[$element, $subSection] = $this->handleFlowCreateAnalytics($analyticsParams);
		$this->sendFlowCreateFinishAnalytics($flow, $element, $subSection);

		return $flow;
	}

	private function handleFlowCreateAnalytics(array $analyticsParams): array
	{
		$context = empty($analyticsParams['context']) ? 'flows_grid' : $analyticsParams['context'];

		$guideFlow = $analyticsParams['guideFlow'] ?? '';
		$element = $guideFlow === 'Y' ? 'guide_button' : 'create_button';
		$subSection = $guideFlow === 'Y' ? 'flow_guide' : $context;

		return [$element, $subSection];
	}

	/**
	 * @restMethod tasks.flow.Flow.update
	 */
	public function updateAction(FlowDto $flowData, array $analyticsParams = []): ?\Bitrix\Tasks\Flow\Flow
	{
		if (!FlowFeature::isFeatureEnabled())
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$flowData->checkPrimary();

		if (
			!FlowAccessController::can(
				$this->userId,
				FlowAction::SAVE,
				$flowData->id,
				FlowModel::createFromArray($flowData)
			)
		)
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$updateCommand = UpdateCommand::createFromArray($flowData);

		if ($flowData->isTaskCreatorsFilled())
		{
			$updateCommand->setTaskCreators($this->converter::convertToFinderCodes($flowData->taskCreators));
		}

		if ($flowData->isResponsibleListFilled())
		{
			$updateCommand->setResponsibleList($this->converter::convertToFinderCodes($flowData->responsibleList));
		}

		try
		{
			$service = new ProjectMembersProxyDecorator($this->service);

			$flow = $service->update($updateCommand);
		}
		catch (InvalidCommandException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		[$element, $subSection] = $this->handleFlowEditAnalytics($analyticsParams);
		if (!empty($subSection))
		{
			$this->sendFlowEditFinishAnalytics($flow, $element, $subSection);
		}

		return $flow;
	}

	private function handleFlowEditAnalytics(array $analyticsParams): array
	{
		$element = Analytics::ELEMENT['save_changes_button'];
		$subSection = $analyticsParams['context'] ?? '';

		return [$element, $subSection];
	}

	/**
	 * @restMethod tasks.flow.Flow.delete
	 */
	public function deleteAction(FlowDto $flowData): ?array
	{
		$flowData->checkPrimary();

		if (!FlowAccessController::can($this->userId, FlowAction::DELETE, $flowData->id))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$deleteCommand = DeleteCommand::createFromArray($flowData);

		try
		{
			$this->service->delete($deleteCommand);
		}
		catch (InvalidCommandException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		return [
			'deleted' => true,
		];
	}

	/**
	 * @restMethod tasks.flow.Flow.activateDemo
	 */
	public function activateDemoAction(FlowDto $flowData): ?\Bitrix\Tasks\Flow\Flow
	{
		$trialFeatureEnabled = false;

		if (!FlowFeature::isFeatureEnabled())
		{
			if (FlowFeature::canTurnOnTrial())
			{
				FlowFeature::turnOnTrial();

				$trialFeatureEnabled = true;
			}
			else
			{
				return $this->buildErrorResponse($this->getAccessDeniedError());
			}
		}

		$flowData->checkPrimary();

		if (!FlowAccessController::can($this->userId, FlowAction::UPDATE, $flowData->id))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$updateCommand = UpdateCommand::createFromArray($flowData);

		if ($flowData->hasTaskCreators())
		{
			$updateCommand->setTaskCreators($this->converter::convertToFinderCodes($flowData->taskCreators));
		}

		if ($flowData->hasResponsibleList())
		{
			$updateCommand->setResponsibleList($this->converter::convertToFinderCodes($flowData->responsibleList));
		}

		$updateCommand->setActive(true);
		$updateCommand->setDemo(false);
		$updateCommand->setActivity(new DateTime());

		try
		{
			$currentFlow = $this->provider->getFlow($flowData->id, ['CREATOR_ID']);
			$updateCommand->setCreatorId($currentFlow->getCreatorId());

			$service = new EmptyOwnerDecorator(
				new ProjectProxyDecorator(
					$this->service
				)
			);

			$flow = $service->update($updateCommand);

			$flow->setTrialFeatureEnabled($trialFeatureEnabled);
		}
		catch (InvalidCommandException|AutoCreationException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		$this->sendFlowCreateFinishAnalytics($flow, Analytics::ELEMENT['create_demo_button']);

		return $flow;
	}

	/**
	 * @restMethod tasks.flow.Flow.getFeatureParams
	 */
	public function getFeatureParamsAction(): array
	{
		return [
			'isFeatureTrialable' => FlowFeature::isFeatureEnabledByTrial(),
		];
	}

	/**
	 * @restMethod tasks.flow.Flow.isExists
	 */
	public function isExistsAction(FlowDto $flowData): ?array
	{
		try
		{
			$flowData->validateName(true);

			return [
				'exists' => $this->provider->isSameFlowExists($flowData->name, $flowData->id)
			];
		}
		catch (InvalidArgumentException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable)
		{
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}
	}

	public function activateAction(int $flowId): ?bool
	{
		if (!FlowAccessController::can($this->userId, FlowAction::UPDATE, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$flow = $this->provider->getFlow($flowId);

			$updateCommand = (new UpdateCommand())->setId($flow->getId())->setActive(!$flow->isActive());

			$this->service->update($updateCommand);
		}
		catch (FlowNotFoundException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		return true;
	}

	/**
	 * Pin flow in flow list for current user
	 *
	 * @restMethod tasks.flow.Flow.pin
	 */
	public function pinAction(int $flowId): ?bool
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$pinOption = $this->flowUserOptionService->changePinOption($flowId, $this->userId);
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		return $pinOption->getValue() === 'Y';
	}

	/**
	 * Get department users count
	 * @restMethod tasks.flow.Flow.getDepartmentMembersCount
	 *
	 * @param array $departments array of departments we want to count like [['department', '15'], ['department', '13:F']].
	 * @return ?array Returns map with EntitySelector codes like [['15', 6], ['13:F', 15]].
	 */
	public function getDepartmentsMemberCountAction(array $departments): ?array
	{
		if (empty($departments))
		{
			return [];
		}

		try
		{
			$countMap = [];

			$departmentService = new DepartmentService();
			foreach ($departments as $department)
			{
				// Allow only department entities
				if ($department[0] !== 'department')
				{
					continue;
				}

				$accessCode = $this->converter::convertToFinderCodes([$department]);
				$count = isset($accessCode[0])
					? $departmentService->getDepartmentUsersCountByAccessCode($accessCode[0])
					: 0
				;

				$countMap[] = [
					'departmentId' => $department[1],
					'count' => $count,
				];
			}
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		return $countMap;
	}

	private function sendFlowCreateFinishAnalytics(
		\Bitrix\Tasks\Flow\Flow $flow,
		string $element,
		string $subSection = 'flows_grid'
	): void
	{
		Analytics::getInstance($this->userId)->onFlowCreate(
			Analytics::EVENT['flow_create_finish'],
			Analytics::SECTION['tasks'],
			Analytics::ELEMENT[$element],
			Analytics::SUB_SECTION[$subSection],
			$this->getFlowAnalyticsAdditionalParams($flow),
		);
	}

	private function sendFlowEditFinishAnalytics(
		\Bitrix\Tasks\Flow\Flow $flow,
		string $element,
		string $subSection = 'flows_grid'
	): void
	{
		Analytics::getInstance($this->userId)->onFlowEdit(
			Analytics::EVENT['flow_edit_finish'],
			Analytics::SECTION['tasks'],
			Analytics::ELEMENT[$element],
			Analytics::SUB_SECTION[$subSection],
			$this->getFlowAnalyticsAdditionalParams($flow),
		);
	}

	private function getFlowAnalyticsAdditionalParams(\Bitrix\Tasks\Flow\Flow $flow): array
	{
		$demoSuffix = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

		return [
			'p1' => 'isDemo_' . $demoSuffix,
			'p2' => 'distributionType_' . $flow->getDistributionType()->value,
			'p3' => 'useTemplate_' . (($flow->getTemplateId() !== 0) ? 'Y' : 'N'),
			'p4' => 'changeDeadline_' . ($flow->canResponsibleChangeDeadline() ? 'Y' : 'N'),
			'p5' => 'flowId_' . $flow->getId(),
		];
	}

	/**
	 * @restMethod tasks.flow.flow.list
	 */
	public function listAction(
		PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = [],
		array $group = [],
	): ?array
	{
		if (!$this->checkOrder($order))
		{
			return $this->buildErrorResponse('Incorrect sort field');
		}

		if (!$this->checkSelect($select))
		{
			return $this->buildErrorResponse('Invalid select data');
		}

		if (!$this->checkFilter($filter))
		{
			return $this->buildErrorResponse('Invalid filter data');
		}

		if (!$this->checkGroup($group))
		{
			return $this->buildErrorResponse('Invalid group data');
		}

		$preparedSelect = $this->prepareSelect($select);
		$preparedFilter = $this->prepareFilter($filter);

		$flowQuery = new FlowQuery($this->userId);
		$flowQuery
			->setSelect($preparedSelect)
			->setWhere($preparedFilter)
			->setLimit($pageNavigation->getLimit())
			->setOffset($pageNavigation->getOffset())
			->setOrderBy($order)
			->setGroupBy($group)
		;

		try
		{
			$listFlowData = $this->provider->getList($flowQuery)->toJson();
		}
		catch (Throwable $e)
		{
			$this->log($e);

			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		return array_values($listFlowData);
	}

	private function checkSelect(array $select): bool
	{
		$availableKeys = array_keys($this->provider->getFlowFields(false));
		$availableKeys[] = '*';

		return empty(array_diff($select, $availableKeys));
	}

	private function checkOrder(array $order): bool
	{
		$orderKeys = array_keys(array_change_key_case($order, CASE_UPPER));
		$availableKeys = array_keys($this->provider->getFlowFields(false));

		return empty(array_diff($orderKeys, $availableKeys));
	}

	private function checkFilter(array $filterValues): bool
	{
		$availableKeys = $this->provider->getFlowFields(false);

		if ($this->validateFilter($filterValues, $availableKeys))
		{
			return true;
		}

		return false;
	}

	private function checkGroup(array $group): bool
	{
		$availableKeys = array_keys($this->provider->getFlowFields(false));

		return empty(array_diff($group, $availableKeys));
	}

	private function prepareSelect(array $select): array
	{
		$availableKeys = array_keys($this->provider->getFlowFields(false));
		$select = (!empty($select) && !in_array('*', $select, true) ? $select : $availableKeys);
		if (!in_array('ID', $select))
		{
			$select[] = 'ID';
		}

		return array_intersect($select, $availableKeys);
	}

	private function prepareFilter(array $filterValues): ConditionTree
	{
		$newFilter = [];

		foreach ($filterValues as $filterFieldName => $filterValue)
		{
			$prefix = $this->filter->extractPrefix($filterFieldName);
			$filterName = str_replace($prefix, '', $filterFieldName);

			if ($prefix === '')
			{
				$newFilter[] = [$filterName, $filterValue];
			}
			else
			{
				$newFilter[] = [$filterName, Filter::CORRECT_FILTER_FIELD_PREFIXES[$prefix], $filterValue];
			}
		}

		return ConditionTree::createFromArray($newFilter);
	}

	private function validateFilter(array $filter, array $allowedFields): bool
	{
		return $this->filter->validate($filter, $allowedFields)->isSuccess();
	}
}
