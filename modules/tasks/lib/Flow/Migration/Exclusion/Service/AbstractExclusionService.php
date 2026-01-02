<?php

namespace Bitrix\Tasks\Flow\Migration\Exclusion\Service;

use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Migration\Strategy\MigrationStrategyInterface;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;

abstract class AbstractExclusionService
{
	/**
	 * @return MigrationStrategyInterface[]
	 */
	abstract protected function getStrategySequence(): array;

	public function excludeByAccessCode(int $flowId, Role $excludedRole, string $excludedAccessCode): StrategyResult
	{
		foreach ($this->getStrategySequence() as $strategy)
		{
			$result = $strategy->migrate($flowId, $excludedRole, $excludedAccessCode);

			if ($result->isStrategyApplied())
			{
				return $result;
			}
		}

		return new StrategyResult();
	}
}
