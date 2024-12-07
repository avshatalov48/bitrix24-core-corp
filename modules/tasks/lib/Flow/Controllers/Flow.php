<?php

namespace Bitrix\Tasks\Flow\Controllers;

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
use Bitrix\Tasks\Flow\Control\Decorator\ProjectProxyDecorator;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\Exception\AutoCreationException;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\MembersProvider;
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
	protected MembersProvider $membersProvider;
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
		$this->membersProvider = new MembersProvider();
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
			$taskCreators = $this->converter::convertFromFinderCodes($this->membersProvider->getTaskCreators($flow->getId()));
			$responsibleQueue = $this->queueProvider->getResponsibleQueue($flow->getId())->getUserIds();
			$options = $this->optionProvider->getOptions($flow->getId());
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
			->setTaskCreators($taskCreators)
			->setResponsibleQueue($responsibleQueue)
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
			->setTaskCreators($this->converter::convertToFinderCodes($flowData->taskCreators));

		try
		{
			$service = new EmptyOwnerDecorator(
				new ProjectProxyDecorator(
					$this->service
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

		try
		{
			$flow = $this->service->update($updateCommand);
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
