<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotAddedException;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\Trait\AddUsersToGroupTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;

final class AddObserver implements AddObserverInterface
{
	use OptionTrait;
	use AddUsersToGroupTrait;

	protected AddCommand $command;
	protected FlowEntity $flowEntity;
	protected OptionService $optionService;
	protected NotificationService $notificationService;

	protected array $notificationItems = [];

	public function __construct()
	{
		$this->optionService = OptionService::getInstance();
		$this->notificationService = new NotificationService();
	}

	/**
	 * @throws FlowNotAddedException
	 */
	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		$this->notificationItems = [];
		$this->command = $command;
		$this->flowEntity = $flowEntity;

		if (
			$this->command->distributionType === Flow::DISTRIBUTION_TYPE_MANUALLY
			&& $this->command->manualDistributorId <= 0
		)
		{
			throw new FlowNotAddedException('Empty manual distributor');
		}

		if ($this->hasManualDistributor())
		{
			$this->optionService->save(
				$this->flowEntity->getId(),
				OptionDictionary::MANUAL_DISTRIBUTOR_ID->value,
				$this->command->manualDistributorId,
			);
			$this->addUsersToGroup($this->flowEntity->getGroupId(), [$this->command->manualDistributorId]);
		}

		$this->optionService->save(
			$this->flowEntity->getId(),
			OptionDictionary::RESPONSIBLE_CAN_CHANGE_DEADLINE->value,
			$this->command->responsibleCanChangeDeadline,
		);

		$this->optionService->save(
			$this->flowEntity->getId(),
			OptionDictionary::MATCH_WORK_TIME->value,
			$this->command->matchWorkTime,
		);

		$this->optionService->save(
			$this->flowEntity->getId(),
			OptionDictionary::NOTIFY_AT_HALF_TIME->value,
			$this->command->notifyAtHalfTime,
		);

		if ($command->notifyOnQueueOverflow)
		{
			$this->optionService->save(
				$flowEntity->getId(),
				OptionDictionary::NOTIFY_ON_QUEUE_OVERFLOW->value,
				$command->notifyOnQueueOverflow,
			);
		}

		if ($command->notifyOnTasksInProgressOverflow)
		{
			$this->optionService->save(
				$flowEntity->getId(),
				OptionDictionary::NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW->value,
				$command->notifyOnTasksInProgressOverflow,
			);
		}

		if ($command->notifyWhenEfficiencyDecreases)
		{
			$this->optionService->save(
				$flowEntity->getId(),
				OptionDictionary::NOTIFY_WHEN_EFFICIENCY_DECREASES->value,
				$command->notifyWhenEfficiencyDecreases,
			);
		}

		$this->optionService->save(
			$flowEntity->getId(),
			OptionDictionary::TASK_CONTROL->value,
			$command->taskControl,
		);

		$this->notificationService->saveConfig($flowEntity->getId(), $this->optionService);
	}
}