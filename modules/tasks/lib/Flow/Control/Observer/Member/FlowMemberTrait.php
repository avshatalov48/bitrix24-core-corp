<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Flow\Control\Observer\Option\OptionTrait;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMember;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;

trait FlowMemberTrait
{
	use OptionTrait;
	/**
	 * @throws SqlQueryException
	 */
	protected function cleanUp(int $flowId): void
	{
		FlowMemberTable::deleteByFlowId($flowId);
	}

	protected function getMembers(): FlowMemberCollection
	{
		$members = $this->mapper->map($this->command);
		$members->add($this->getOwner());
		$members->add($this->getCreator());

		if ($this->hasManualDistributor())
		{
			$members->add($this->getManualDistributor());
		}

		if ($this->hasQueue())
		{
			$members->merge($this->getQueue());
		}

		return $members;
	}

	private function getOwner(): FlowMember
	{
		return $this->getMember($this->flowEntity->getOwnerId(), Role::OWNER);
	}

	private function getCreator(): FlowMember
	{
		return $this->getMember($this->flowEntity->getCreatorId(), Role::CREATOR);
	}

	private function getManualDistributor(): FlowMember
	{
		return $this->getMember($this->command->manualDistributorId, Role::MANUAL_DISTRIBUTOR);
	}

	private function getQueue(): FlowMemberCollection
	{
		$collection = new FlowMemberCollection();
		foreach ($this->command->responsibleQueue as $userId)
		{
			$member = (new FlowMember())
				->setFlowId($this->flowEntity->getId())
				->setAccessCode('U' . $userId)
				->setEntityId($userId)
				->setEntityType('U')
				->setRole(Role::QUEUE_ASSIGNEE->value);

			$collection->add($member);
		}

		return $collection;
	}

	private function getMember(int $userId, Role $role): FlowMember
	{
		return (new FlowMember())
			->setFlowId($this->flowEntity->getId())
			->setAccessCode('U' . $userId)
			->setEntityId($userId)
			->setEntityType('U')
			->setRole($role->value);
	}
}