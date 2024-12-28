<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;

class UpdateObserver implements UpdateObserverInterface
{
	use FlowMemberTrait;

	protected UpdateCommand $command;
	protected FlowEntity $flowEntity;

	/**
	 * @throws FlowNotUpdatedException
	 * @throws ArgumentException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$this->command = $command;
		$this->flowEntity = $flowEntity;

		if (!$this->doNeedToUpdate())
		{
			return;
		}

		$this->cleanUpByChanges($flowEntityBeforeUpdate);

		$members = $this->getMembers($command, $flowEntity);

		if ($members->isEmpty())
		{
			throw new FlowNotUpdatedException('Empty flow members list');
		}

		$members
			->makeUnique()
			->insertIgnore();
	}

	/**
	 * @throws ArgumentException
	 */
	//TODO refactor by distributionType
	private function cleanUpByChanges(FlowEntity $flowEntityBeforeUpdate): void
	{
		if (isset($this->command->taskCreators))
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::TASK_CREATOR->value);
		}

		if (isset($this->command->ownerId))
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::OWNER->value);
		}

		if (isset($this->command->creatorId))
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::CREATOR->value);
		}

		$flowEntityBeforeUpdateDistributionType = $flowEntityBeforeUpdate->getDistributionType();
		$flowEntityDistributionType = $this->flowEntity->getDistributionType();

		$isSwitchedFromQueue =
			$flowEntityBeforeUpdateDistributionType === FlowDistributionType::QUEUE->value
			&& $flowEntityDistributionType !== FlowDistributionType::QUEUE->value
		;

		if ($this->hasResponsibleQueue() || $isSwitchedFromQueue)
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::QUEUE_ASSIGNEE->value);
		}

		$isSwitchedFromManual =
			$flowEntityBeforeUpdateDistributionType === FlowDistributionType::MANUALLY->value
			&& $flowEntityDistributionType !== FlowDistributionType::MANUALLY->value
		;

		if ($this->hasManualDistributor() || $isSwitchedFromManual)
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::MANUAL_DISTRIBUTOR->value);
		}

		$isSwitchedFromHimself =
			$flowEntityBeforeUpdateDistributionType === FlowDistributionType::HIMSELF->value
			&& $flowEntityDistributionType !== FlowDistributionType::HIMSELF->value
		;

		if ($this->hasResponsibleHimself() || $isSwitchedFromHimself)
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::HIMSELF_ASSIGNED->value);
		}
	}

	private function doNeedToUpdate(): bool
	{
		return
			isset($this->command->taskCreators)
			|| isset($this->command->ownerId)
			|| isset($this->command->creatorId)
			|| $this->hasResponsibleQueue()
			|| $this->hasManualDistributor()
			|| $this->hasResponsibleHimself()
		;
	}
}