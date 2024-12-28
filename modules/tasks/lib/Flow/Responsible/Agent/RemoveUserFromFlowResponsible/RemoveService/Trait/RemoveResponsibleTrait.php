<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\Trait;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

trait RemoveResponsibleTrait
{
	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws FlowNotFoundException
	 * @throws LoaderException
	 * @throws InvalidCommandException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function removeUserFromFlowsResponsible(int $deletedUserId, array $flowIds): void
	{
		foreach ($flowIds as $flowId)
		{
			$this->removeUserFromResponsibleByFlowId($deletedUserId, $flowId);
		}
	}

	/**
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws FlowNotFoundException
	 * @throws LoaderException
	 * @throws SystemException
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws InvalidCommandException
	 * @throws ArgumentException
	 */
	private function removeUserFromResponsibleByFlowId(int $deletedUserId, int $flowId): void
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');

		try
		{
			$responsibleAccessCodes = $memberFacade->getResponsibleAccessCodes($flowId);
		}
		catch (\Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException $e)
		{
			$this->removeAllResponsibleByFlowId($flowId);

			return;
		}

		$userIds = (new AccessCodeConverter(...$responsibleAccessCodes))
			->getUserIds()
		;

		$userIdsFiltered = array_values(array_filter($userIds, static fn($userId) => $userId !== $deletedUserId));
		if (empty($userIdsFiltered))
		{
			$this->migrateFlowToManualDistribution($flowId);
		}
		else
		{
			$this->saveFlowWithoutDeletedUser($flowId, $userIdsFiltered);
		}
	}
}