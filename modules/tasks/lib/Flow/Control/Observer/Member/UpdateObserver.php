<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Control\Mapper\CreatorsCommandMapper;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;

class UpdateObserver implements UpdateObserverInterface
{
	use FlowMemberTrait;

	protected CreatorsCommandMapper $mapper;
	protected UpdateCommand $command;
	protected FlowEntity $flowEntity;

	public function __construct()
	{
		$this->mapper = new CreatorsCommandMapper();
	}

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

		$members = $this->getMembers();

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

		$isSwitchedFromQueue =
			$flowEntityBeforeUpdate->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE
			&& $this->flowEntity->getDistributionType() !== Flow::DISTRIBUTION_TYPE_QUEUE
		;

		if ($this->hasQueue() || $isSwitchedFromQueue)
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::QUEUE_ASSIGNEE->value);
		}

		$isSwitchedFromManual =
			$flowEntityBeforeUpdate->getDistributionType() === Flow::DISTRIBUTION_TYPE_MANUALLY
			&& $this->flowEntity->getDistributionType() !== Flow::DISTRIBUTION_TYPE_MANUALLY
		;

		if ($this->hasManualDistributor() || $isSwitchedFromManual)
		{
			FlowMemberTable::deleteByRole($this->command->id, Role::MANUAL_DISTRIBUTOR->value);
		}
	}

	private function doNeedToUpdate(): bool
	{
		return
			isset($this->command->taskCreators)
			|| isset($this->command->ownerId)
			|| isset($this->command->creatorId)
			|| $this->hasQueue()
			|| $this->hasManualDistributor()
		;
	}
}