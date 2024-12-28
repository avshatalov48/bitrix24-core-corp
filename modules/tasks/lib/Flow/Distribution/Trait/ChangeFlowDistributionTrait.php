<?php

namespace Bitrix\Tasks\Flow\Distribution\Trait;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

trait ChangeFlowDistributionTrait
{
	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws SqlQueryException
	 * @throws CommandNotFoundException
	 * @throws ObjectPropertyException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function migrateFlowToManualDistribution(int $flowId): void
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow)
		{
			return;
		}

		$ownerId = $flow->getOwnerId();
		$owner = UserTable::getById($ownerId)->fetchObject();
		$newDistributorId = (bool)$owner?->getActive() === false ? User::getAdminId() : $ownerId;

		$command =
			(new UpdateCommand())
				->setId($flowId)
				->setDistributionType(FlowDistributionType::MANUALLY->value)
				->setResponsibleList(["U{$newDistributorId}"])
		;

		$this->save($command);
		$this->onMigrateToManualDistributor($flowId);
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	private function save(UpdateCommand $command): void
	{
		$flowService = ServiceLocator::getInstance()->get('tasks.flow.service');
		$flowService->update($command);
	}

	abstract protected function onMigrateToManualDistributor(int $flowId): void;
}