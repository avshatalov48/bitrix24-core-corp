<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Consistency;

interface ConsistencyResolverInterface
{
	public function resolve(string $context): ConsistencyWrapper;
}
