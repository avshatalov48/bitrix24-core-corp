<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Tasks\Flow\Notification\Command\ForcedManualDistributorChangeAbsentCommand;
use Bitrix\Tasks\Flow\Notification\Command\ForcedManualDistributorChangeAbsentCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\ForcedManualDistributorChangeCommand;
use Bitrix\Tasks\Flow\Notification\Command\ForcedManualDistributorChangeCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSwitchToManualDistributionAbsentCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSwitchToManualDistributionAbsentCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSwitchToManualDistributionCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSwitchToManualDistributionCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutNewTaskCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutNewTaskCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\DeleteConfigCommand;
use Bitrix\Tasks\Flow\Notification\Command\DeleteConfigCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutBusyResponsibleCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutBusyResponsibleCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSlowEfficiencyCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSlowEfficiencyCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSlowQueueCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyAboutSlowQueueCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\NotifyHimselfMembersAboutNewTaskCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyHimselfMembersAboutNewTaskHandler;
use Bitrix\Tasks\Flow\Notification\Command\PingManualDistributorAboutNewTaskCommand;
use Bitrix\Tasks\Flow\Notification\Command\PingManualDistributorAboutNewTaskCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\SaveConfigCommand;
use Bitrix\Tasks\Flow\Notification\Command\SaveConfigCommandHandler;
use Bitrix\Tasks\Flow\Notification\Command\UpdatePingCommand;
use Bitrix\Tasks\Flow\Notification\Command\UpdatePingCommandHandler;
use Bitrix\Tasks\Flow\Notification\Config\Caption;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Option\Option;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Provider\TaskProvider;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class NotificationService
{
	private ConfigRepository $configRepository;
	private SyncAgent $syncAgent;
	private PingAgent $pingAgent;
	private Manager $bizProcess;
	private TaskProvider $taskProvider;
	private FlowProvider $flowProvider;
	private ThrottleProvider $throttleProvider;
	private Presets $presets;
	private HimselfFlowAgent $himselfFlowAgent;

	public function __construct()
	{
		$this->configRepository = new ConfigRepository();
		$this->syncAgent = new SyncAgent();
		$this->pingAgent = new PingAgent();
		$this->bizProcess = new Manager();
		$this->taskProvider = new TaskProvider();
		$this->flowProvider = new FlowProvider();
		$this->throttleProvider = new ThrottleProvider();
		$this->himselfFlowAgent = new HimselfFlowAgent();
		$this->presets = new Presets();
	}

	public function saveConfig(int $flowId, OptionService $optionService, $forceSync = false): void
	{
		$items = $this->getDefaultSettings();

		foreach ($optionService->getOptions($flowId) as $option)
		{
			$item = match ($option->getName())
			{
				OptionDictionary::NOTIFY_WHEN_EFFICIENCY_DECREASES->value => $this->getSlowEfficiencySetting($option),
				OptionDictionary::NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW->value => $this->getTasksInProgressSetting($option),
				OptionDictionary::NOTIFY_ON_QUEUE_OVERFLOW->value => $this->getBusyQueueSetting($option),
				OptionDictionary::NOTIFY_AT_HALF_TIME->value => $this->getHalfTimeSetting($option),
				OptionDictionary::NOTIFY_WHEN_TASK_NOT_TAKEN->value => $this->getTaskNotTakenSetting($option),
				default => null,
			};

			if ($item)
			{
				$items[] = $item;
			}
		}

		$command = new SaveConfigCommand($flowId, $items, $forceSync);
		$commandHandler = (new SaveConfigCommandHandler($this->configRepository, $this->syncAgent));

		$commandHandler($command);
	}

	public function deleteConfig(int $flowId): void
	{
		$command = new DeleteConfigCommand($flowId);
		$commandHandler = (new DeleteConfigCommandHandler($this->configRepository, $this->syncAgent));

		$commandHandler($command);
	}

	public function onTaskToFlowAdded(int $taskId, int $flowId): void
	{
		$command = new NotifyAboutNewTaskCommand($taskId, $flowId);
		$commandHandler = $this->getHandler(NotifyAboutNewTaskCommand::class);
		$commandHandler($command);

		$slowQueueCommand = new NotifyAboutSlowQueueCommand($flowId);
		$slowQueueCommandHandler = $this->getHandler(NotifyAboutSlowQueueCommand::class);
		$slowQueueCommandHandler($slowQueueCommand);

		$pingManualDistributorAboutNewTask = new PingManualDistributorAboutNewTaskCommand($taskId, $flowId);
		$pingManualDistributorAboutNewTaskHandler = new PingManualDistributorAboutNewTaskCommandHandler();
		$pingManualDistributorAboutNewTaskHandler($pingManualDistributorAboutNewTask);

		$pingHimselfDistributorAboutNewTask = new NotifyHimselfMembersAboutNewTaskCommand($taskId, $flowId);
		$pingHimselfDistributorAboutNewTaskHandler = new NotifyHimselfMembersAboutNewTaskHandler();
		$pingHimselfDistributorAboutNewTaskHandler($pingHimselfDistributorAboutNewTask);
	}

	public function onSwitchToManualDistribution(int $flowId): void
	{
		$command = new NotifyAboutSwitchToManualDistributionCommand($flowId);
		$commandHandler = $this->getHandler(NotifyAboutSwitchToManualDistributionCommand::class);
		$commandHandler($command);
	}

	public function onSwitchToManualDistributionAbsent(int $flowId): void
	{
		$command = new NotifyAboutSwitchToManualDistributionAbsentCommand($flowId);
		$commandHandler = $this->getHandler(NotifyAboutSwitchToManualDistributionAbsentCommand::class);
		$commandHandler($command);
	}

	public function onForcedManualDistributorChange(int $flowId): void
	{
		$command = new ForcedManualDistributorChangeCommand($flowId);
		$commandHandler = $this->getHandler(ForcedManualDistributorChangeCommand::class);
		$commandHandler($command);
	}

	public function onForcedManualDistributorAbsentChange(int $flowId): void
	{
		$command = new ForcedManualDistributorChangeAbsentCommand($flowId);
		$commandHandler = $this->getHandler(ForcedManualDistributorChangeAbsentCommand::class);
		$commandHandler($command);
	}

	public function onTaskExpireTimeChange(int $taskId): void
	{
		$command = new UpdatePingCommand($taskId);
		$commandHandler = (new UpdatePingCommandHandler(
			$this->configRepository,
			$this->pingAgent,
			$this->himselfFlowAgent,
		));

		$commandHandler($command);
	}

	public function onTaskStatusChanged(int $taskId): void
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);
		$task->fillFlowTask();

		if (!$task->getFlowId())
		{
			return;
		}

		$slowQueueCommand = new NotifyAboutSlowQueueCommand($task->getFlowId());
		$slowQueueCommandHandler = $this->getHandler(NotifyAboutSlowQueueCommand::class);
		$slowQueueCommandHandler($slowQueueCommand);

		$busyResponsibleCommand = new NotifyAboutBusyResponsibleCommand($task->getFlowId());
		$busyResponsibleCommandHandler = $this->getHandler(NotifyAboutBusyResponsibleCommand::class);
		$busyResponsibleCommandHandler($busyResponsibleCommand);
	}

	public function onEfficiencyChanged(int $flowId): void
	{
		$command = new NotifyAboutSlowEfficiencyCommand($flowId);
		$commandHandler = new NotifyAboutSlowEfficiencyCommandHandler(
			$this->configRepository,
			$this->bizProcess,
			$this->flowProvider,
			$this->throttleProvider,
		);

		$commandHandler($command);
	}

	private function getHandler(string $class)
	{
		return match($class)
		{
			NotifyAboutSlowQueueCommand::class => new NotifyAboutSlowQueueCommandHandler($this->configRepository, $this->bizProcess, $this->taskProvider, $this->throttleProvider),
			NotifyAboutBusyResponsibleCommand::class => new NotifyAboutBusyResponsibleCommandHandler($this->configRepository, $this->bizProcess, $this->taskProvider, $this->throttleProvider),
			NotifyAboutNewTaskCommand::class => new NotifyAboutNewTaskCommandHandler($this->configRepository, $this->pingAgent, $this->bizProcess, $this->himselfFlowAgent),
			NotifyAboutSwitchToManualDistributionCommand::class => new NotifyAboutSwitchToManualDistributionCommandHandler($this->configRepository, $this->bizProcess, $this->flowProvider),
			NotifyAboutSwitchToManualDistributionAbsentCommand::class => new NotifyAboutSwitchToManualDistributionAbsentCommandHandler($this->configRepository, $this->bizProcess, $this->flowProvider),
			ForcedManualDistributorChangeCommand::class => new ForcedManualDistributorChangeCommandHandler($this->configRepository, $this->bizProcess, $this->flowProvider),
			ForcedManualDistributorChangeAbsentCommand::class => new ForcedManualDistributorChangeAbsentCommandHandler($this->configRepository, $this->bizProcess, $this->flowProvider),
		};
	}

	private function getDefaultSettings(): array
	{
		$switchToManual = $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION')
		);
		$switchToManualAbsent = $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT')
		);
		$forcedManualDistributorChange = $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE')
		);
		$forcedManualDistributorChangeAbsent = $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT')
		);

		return [
			$switchToManual,
			$switchToManualAbsent,
			$forcedManualDistributorChange,
			$forcedManualDistributorChangeAbsent,
		];
	}

	private function getTaskNotTakenSetting(Option $option): ?Item
	{
		return $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_HIMSELF_ADMIN_TASK_NOT_TAKEN'),
			(int)$option->getValue(),
		);
	}

	private function getSlowEfficiencySetting(Option $option): ?Item
	{
		return $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_EFFICIENCY_LOWER'),
			(int)$option->getValue(),
		);
	}

	private function getTasksInProgressSetting(Option $option): ?Item
	{
		return $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_BUSY_RESPONSIBLE'),
			(int)$option->getValue(),
		);
	}

	private function getBusyQueueSetting(Option $option): ?Item
	{
		return $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_BUSY_QUEUE'),
			(int)$option->getValue(),
		);
	}

	private function getHalfTimeSetting(Option $option): ?Item
	{
		if ((bool)$option->getValue() === false)
		{
			return null;
		}

		return $this->presets->getItemByCaption(
			new Caption('TASKS_FLOW_NOTIFICATION_CAPTION_HALF_TIME_BEFORE_EXPIRE'),
			(int)$option->getValue(),
		);
	}
}