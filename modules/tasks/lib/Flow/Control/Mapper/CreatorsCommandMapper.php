<?php

namespace Bitrix\Tasks\Flow\Control\Mapper;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMember;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;

class CreatorsCommandMapper
{
	public function map(AddCommand|UpdateCommand $command): FlowMemberCollection
	{
		$collection = new FlowMemberCollection();

		if (empty($command->taskCreators))
		{
			return $collection;
		}

		foreach ($command->taskCreators as $accessCode)
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
				->setRole(Role::TASK_CREATOR->value);

			if ($command->hasId())
			{
				$object->setFlowId($command->id);
			}

			$collection->add($object);
		}

		return $collection;
	}
}