<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Main\LoaderException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;

final class UpdateObserver implements UpdateObserverInterface
{
	use OptionTrait;

	protected UpdateCommand $command;
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
	 * @throws LoaderException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$this->notificationItems = [];
		$this->command = $command;
		$this->flowEntity = $flowEntity;
		$flowId = $flowEntity->getId();
		$busyQueue = $command->notifyOnQueueOverflow;
		$inProgress = $command->notifyOnTasksInProgressOverflow;
		$efficiency = $command->notifyWhenEfficiencyDecreases;
		$isDistributionTypeChanged = $flowEntity->getDistributionType() !== $flowEntityBeforeUpdate->getDistributionType();

		if ($this->command->distributionType !== null && $this->hasManualDistributor())
		{
			$manualDistributorAccessCode = $this->command->responsibleList[0];
			$manualDistributorId = (new AccessCodeConverter($manualDistributorAccessCode))
				->getAccessCodeIdList()[0]
			;

			$this->optionService->save(
				$this->flowEntity->getId(),
				OptionDictionary::MANUAL_DISTRIBUTOR_ID->value,
				$manualDistributorId,
			);
		}

		if ($isDistributionTypeChanged)
		{
			$this->onDistributionTypeChange($flowId, $flowEntityBeforeUpdate);
		}

		if ($this->command->responsibleCanChangeDeadline !== null)
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::RESPONSIBLE_CAN_CHANGE_DEADLINE->value,
				$this->command->responsibleCanChangeDeadline,
			);
		}

		if ($this->command->matchWorkTime !== null)
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::MATCH_WORK_TIME->value,
				$this->command->matchWorkTime,
			);
		}

		if ($this->command->matchSchedule !== null)
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::MATCH_SCHEDULE->value,
				$this->command->matchSchedule,
			);
		}

		if ($this->command->notifyAtHalfTime !== null)
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::NOTIFY_AT_HALF_TIME->value,
				$this->command->notifyAtHalfTime,
			);
		}

		if ($busyQueue !== null)
		{
			($busyQueue > 0)
				? $this->optionService->save($flowId, OptionDictionary::NOTIFY_ON_QUEUE_OVERFLOW->value, $busyQueue)
				: $this->optionService->delete($flowId, OptionDictionary::NOTIFY_ON_QUEUE_OVERFLOW->value)
			;
		}

		if ($inProgress !== null)
		{
			($inProgress > 0)
				? $this->optionService->save($flowId, OptionDictionary::NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW->value, $inProgress)
				: $this->optionService->delete($flowId, OptionDictionary::NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW->value)
			;
		}

		if ($efficiency !== null)
		{
			($efficiency > 0)
				? $this->optionService->save($flowId, OptionDictionary::NOTIFY_WHEN_EFFICIENCY_DECREASES->value, $efficiency)
				: $this->optionService->delete($flowId, OptionDictionary::NOTIFY_WHEN_EFFICIENCY_DECREASES->value)
			;
		}

		if ($this->flowEntity->getDistributionType() !== FlowDistributionType::HIMSELF->value)
		{
			$this->optionService->delete($flowId, OptionDictionary::NOTIFY_WHEN_TASK_NOT_TAKEN->value);
		}
		else
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::NOTIFY_WHEN_TASK_NOT_TAKEN->value,
				$this->command->notifyWhenTaskNotTaken,
			);
		}

		if ($this->command->taskControl !== null)
		{
			$this->optionService->save(
				$flowId,
				OptionDictionary::TASK_CONTROL->value,
				$this->command->taskControl,
			);
		}

		$this->notificationService->saveConfig($flowEntity->getId(), $this->optionService);
	}

	private function onDistributionTypeChange(int $flowId, FlowEntity $flowEntityBeforeUpdate): void
	{
		$flowDistributionTypeWasManually = $flowEntityBeforeUpdate->getDistributionType() === FlowDistributionType::MANUALLY->value;
		if ($flowDistributionTypeWasManually)
		{
			$this->optionService->delete($flowId, OptionDictionary::MANUAL_DISTRIBUTOR_ID->value);
		}

		$flowDistributionTypeWasQueue = $flowEntityBeforeUpdate->getDistributionType() === FlowDistributionType::QUEUE->value;
		if ($flowDistributionTypeWasQueue)
		{
			$this->optionService->delete($flowId, OptionDictionary::RESPONSIBLE_QUEUE_LATEST_ID->value);
		}
	}
}