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
	private function cleanUpByChanges(): void
	{
		$deleteRoles = [];

		if (isset($this->command->responsibleList))
		{
			$deleteRoles = array_merge($deleteRoles, Role::getResponsibleRoles());
		}

		if (isset($this->command->taskCreators))
		{
			$deleteRoles[] = Role::TASK_CREATOR->value;
		}

		if (isset($this->command->ownerId))
		{
			$deleteRoles[] = Role::OWNER->value;
		}

		if (isset($this->command->creatorId))
		{
			$deleteRoles[] = Role::CREATOR->value;
		}

		if (!empty($deleteRoles))
		{
			FlowMemberTable::deleteByRoles($this->command->id, $deleteRoles);
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
			|| $this->hasResponsibleImmutable()
		;
	}
}