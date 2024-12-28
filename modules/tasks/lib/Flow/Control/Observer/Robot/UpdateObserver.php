<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Robot;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Kanban\BizProcService;
use Bitrix\Tasks\Flow\Kanban\Command\ReinstallFlowRobotsCommand;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Psr\Container\NotFoundExceptionInterface;

class UpdateObserver implements UpdateObserverInterface
{
	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws ArgumentException
	 * @throws InvalidCommandException
	 * @throws ProviderException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		if ($command->hasOwnerId() && $flowEntity->getOwnerId() !== $flowEntityBeforeUpdate->getOwnerId())
		{
			$service = ServiceLocator::getInstance()->get('tasks.flow.kanban.bizproc.service');
			$reinstallCommand = (new ReinstallFlowRobotsCommand())
				->setFlowId($flowEntity->getId())
				->setProjectId($flowEntity->getGroupId())
				->setOwnerId($flowEntity->getOwnerId());

			$service->reinstall($reinstallCommand);
		}
	}
}