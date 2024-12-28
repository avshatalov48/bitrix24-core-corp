<?php

namespace Bitrix\Tasks\Flow\Attribute;

use Attribute;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;

#[Attribute]
class DistributionType implements CheckInterface
{
	public function check(mixed $value): bool
	{
		return in_array($value, FlowDistributionType::values(), true);
	}
}