<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Consistency;

use Bitrix\Main\Config\Configuration;

class ConfigConsistencyResolver implements ConsistencyResolverInterface
{
	public function resolve(string $context): ConsistencyWrapper
	{
		$config = Configuration::getInstance('tasks')->get('consistency') ?? [];
		if (empty($config))
		{
			return new ConsistencyWrapper(new Strategy\Base());
		}

		$strategyType = $config[$context] ?? null;

		$strategy = match ($strategyType)
		{
			'transaction' => new Strategy\Transaction(),
			'lock' => new Strategy\Lock(),
			default => new Strategy\Base(),
		};

		return new ConsistencyWrapper($strategy);
	}
}
