<?php

namespace Bitrix\Tasks\Flow\Controllers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\EntitySelector\Converter;
use Bitrix\Main\Web\Json;
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
use Bitrix\Tasks\InvalidCommandException;
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
	public function createAction(FlowDto $flowData, string $guideFlow = ''): ?\Bitrix\Tasks\Flow\Flow
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
			->setTaskCreators($this->converter::convertToFinderCodes($flowData->taskCreators))
			->setResponsibleList($this->converter::convertToFinderCodes($flowData->responsibleList));

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

		$element = $guideFlow === 'Y' ? 'guide_button' : 'create_button';
		$subSection = $guideFlow === 'Y' ? 'flow_guide' : 'flows_grid';

		$this->sendFlowCreateFinishAnalytics($element, $subSection);

		return $flow;
	}

	/**
	 * @restMethod tasks.flow.Flow.update
	 */
	public function updateAction(FlowDto $flowData): ?\Bitrix\Tasks\Flow\Flow
	{
		if (!FlowFeature::isFeatureEnabled())
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
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

		return $flow;
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

		$this->sendFlowCreateFinishAnalytics(Analytics::ELEMENT['create_demo_button']);

		return $flow;
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

	private function sendFlowCreateFinishAnalytics(string $element, string $subSection = 'flows_grid'): void
	{
		$demoSuffix = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

		Analytics::getInstance($this->userId)->onFlowCreate(
			Analytics::EVENT['flow_create_finish'],
			Analytics::SECTION['tasks'],
			Analytics::ELEMENT[$element],
			Analytics::SUB_SECTION[$subSection],
			['p1' => 'isDemo_' . $demoSuffix]
		);
	}
}
