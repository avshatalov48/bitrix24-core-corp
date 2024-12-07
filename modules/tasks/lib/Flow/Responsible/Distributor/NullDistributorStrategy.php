<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Flow;

class NullDistributorStrategy implements DistributorStrategyInterface
{
	public function distribute(Flow $flow): int
	{
		return 0;
	}
}