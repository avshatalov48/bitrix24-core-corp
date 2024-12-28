<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Observer\Trait\AddUsersToGroupTrait;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

final class UpdateObserver implements UpdateObserverInterface
{
	use AddUsersToGroupTrait;

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		if (empty($command->responsibleList))
		{
			return;
		}

		$userIds = (new AccessCodeConverter(...$command->responsibleList))
			->getUserIds()
		;

		if (empty($userIds))
		{
			return;
		}

		$this->addUsersToGroup($flowEntity->getGroupId(), $userIds);
	}
}