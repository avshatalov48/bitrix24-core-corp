<?php

namespace Bitrix\Tasks\Flow\Control\Mapper;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMember;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;

class FlowMemberMapper
{
	/**
	 * @param string[] $accessCodeList
	 */
	public function map(AddCommand|UpdateCommand $command, array $accessCodeList, Role $memberRole): FlowMemberCollection
	{
		$collection = new FlowMemberCollection();

		if (empty($accessCodeList))
		{
			return $collection;
		}

		foreach ($accessCodeList as $accessCode)
		{
			if ($accessCode === 'UA')
			{
				$entityId = 0;
				$entityType = 'U';
			}
			else
			{
				$access = new AccessCode($accessCode);
				$entityId = $access->getEntityId();
				$entityType = $access->getEntityPrefix();
			}

			$object = (new FlowMember())
				->setAccessCode($accessCode)
				->setEntityId($entityId)
				->setEntityType($entityType)
				->setRole($memberRole->value);

			if ($command->hasId())
			{
				$object->setFlowId($command->id);
			}

			$collection->add($object);
		}

		return $collection;
	}
}