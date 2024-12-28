<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Flow;

interface DistributorStrategyInterface
{
	public function distribute(Flow $flow, array $fields, array $taskData): int;
}