<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\Trait\AddUsersToGroupTrait;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;

final class AddObserver implements AddObserverInterface
{
	use AddUsersToGroupTrait;

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public function update(AddCommand $command, FlowEntity $flowEntity): void
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