<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Access\Service;

use Bitrix\Tasks\Flow\Control\Command\Access\GiveAccessCommand;
use Bitrix\Tasks\Flow\Migration\Access\Repository\RoleRepositoryInterface;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Migration\Access\Exception\LockNotAcquiredException;
use Bitrix\Main\Application;

final class FlowAccessRightsService
{
	private const LOCK_TIMEOUT = 10;

	public function __construct(
		private readonly RoleRepositoryInterface $roleRepository
	)
	{
	}

	/**
	 * @throws InvalidCommandException
	 * @throws LockNotAcquiredException
	 */
	public function setAccessRights(GiveAccessCommand $command): ?int
	{
		$command->validateAdd();

		$connection = Application::getConnection();
		$lockName = 'tasks_flow_access_rights';

		if (!$connection->lock($lockName, self::LOCK_TIMEOUT))
		{
			throw new LockNotAcquiredException("Could not acquire lock");
		}

		$startFromId = $command->startFromId;
		$limit = $command->limit;

		$roleIds = $this->roleRepository->getList($startFromId, $limit);
		if (empty($roleIds))
		{
			$connection->unlock($lockName);

			return null;
		}

		try
		{
			foreach ($roleIds as $roleId)
			{
				$this->roleRepository->setPermissionForRoleId($roleId);
			}
		}
		finally
		{
			$connection->unlock($lockName);
		}

		return end($roleIds);
	}
}
