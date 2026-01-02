<?php

namespace Bitrix\Tasks\Flow\Migration\Strategy;

use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;

interface MigrationStrategyInterface
{
	public function migrate(int $flowId, ?Role $excludedRole = null, ?string $excludedAccessCode = null): StrategyResult;
}
