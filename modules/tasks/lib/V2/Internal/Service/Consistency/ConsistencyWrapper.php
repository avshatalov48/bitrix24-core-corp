<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Consistency;

use Bitrix\Tasks\V2\Internal\Service\Consistency\Strategy\ConsistencyStrategyInterface;
use Closure;

class ConsistencyWrapper
{
	public function __construct(
		private readonly ConsistencyStrategyInterface $consistencyStrategy
	)
	{

	}

	public function wrap(Closure $closure, array $parameters = []): mixed
	{
		return $this->consistencyStrategy->execute($closure, $parameters);
	}
}