<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowOptionTable;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

class ManuallyRemoveResponsibleService extends AbstractRemoveResponsibleService
{
	protected function getResponsibleRole(): Role
	{
		return Role::MANUAL_DISTRIBUTOR;
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function removeUserFromFlowsResponsible(int $deletedUserId, array $flowIds): void
	{
		foreach ($flowIds as $flowId)
		{
			$flow = FlowRegistry::getInstance()->get($flowId);
			if ($flow === null)
			{
				FlowOptionTable::deleteByFilter(['=FLOW_ID' => $flowId]);

				continue;
			}

			$this->migrateFlowToManualDistribution($flowId);
		}
	}

	protected function onMigrateToManualDistributor(int $flowId): void
	{
		if (FlowFeature::isOn())
		{
			$this->notificationService->onForcedManualDistributorChange($flowId);
		}
	}
}