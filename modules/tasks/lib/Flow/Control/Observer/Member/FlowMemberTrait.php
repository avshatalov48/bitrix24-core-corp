<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Member;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Mapper\FlowMemberMapper;
use Bitrix\Tasks\Flow\Control\Observer\Option\OptionTrait;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
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

	protected function getMembers(AddCommand|UpdateCommand $command, FlowEntity $flowEntity): FlowMemberCollection
	{
		$mapper = new FlowMemberMapper();

		$members = new FlowMemberCollection();
		if (!empty($command->taskCreators))
		{
			$members = $mapper->map($command, $command->taskCreators, Role::TASK_CREATOR);
		}

		$members->add($this->getOwner($flowEntity));
		$members->add($this->getCreator($flowEntity));

		$responsibleRole = $this->getResponsibleRole($flowEntity);
		$responsibleList = $mapper->map($command, $command->responsibleList, $responsibleRole);
		$members->merge($responsibleList);

		return $members;
	}

	private function getOwner(FlowEntity $flowEntity): FlowMember
	{
		return $this->getMember($flowEntity->getId(), $flowEntity->getOwnerId(), Role::OWNER);
	}

	private function getCreator(FlowEntity $flowEntity): FlowMember
	{
		return $this->getMember($flowEntity->getId(), $flowEntity->getCreatorId(), Role::CREATOR);
	}

	private function getMember(int $flowId, int $userId, Role $role): FlowMember
	{
		return (new FlowMember())
			->setFlowId($flowId)
			->setAccessCode('U' . $userId)
			->setEntityId($userId)
			->setEntityType('U')
			->setRole($role->value);
	}

	private function getResponsibleRole(FlowEntity $flowEntity): Role
	{
		$distributionType = FlowDistributionType::from($flowEntity->getDistributionType());

		return (new FlowDistributionServicesFactory($distributionType))
			->getMemberProvider()
			->getResponsibleRole()
		;
	}
}