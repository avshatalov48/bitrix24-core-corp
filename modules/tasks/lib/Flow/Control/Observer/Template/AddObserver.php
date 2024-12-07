<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Template;

use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Psr\Container\NotFoundExceptionInterface;

class AddObserver implements AddObserverInterface
{
	use UpdatePermissionTrait;

	/**
	 * @throws InvalidCommandException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		if ($command->templateId <= 0)
		{
			return;
		}

		$this->updatePermission($command);
	}
}