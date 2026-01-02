<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Consistency\Strategy;

class Base implements ConsistencyStrategyInterface
{
	public function execute(callable $callable, array $parameters = []): mixed
	{
		return $callable();
	}
}