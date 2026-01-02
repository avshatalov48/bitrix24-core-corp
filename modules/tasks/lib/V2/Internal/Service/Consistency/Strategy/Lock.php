<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Consistency\Strategy;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;

class Lock implements ConsistencyStrategyInterface
{
	public function execute(callable $callable, array $parameters = []): mixed
	{
		$lockName = $parameters['lockName'] ?? null;
		if (empty($lockName))
		{
			throw new ArgumentException('Empty', 'lockName');
		}

		$connection = Application::getConnection();

		$lockTimeout = $parameters['lockTimeout'] ?? 0;

		if ($connection->lock($lockName, $lockTimeout))
		{
			try
			{
				return $callable();
			}
			finally
			{
				$connection->unlock($lockName);
			}
		}

		$onLock = $parameters['onLock'] ?? null;
		if (is_callable($onLock))
		{
			return $onLock();
		}

		return null;
	}
}