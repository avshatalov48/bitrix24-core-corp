<?php

namespace Bitrix\Tasks\Flow\Migration\Exclusion\Service\Responsible;

use Bitrix\Tasks\Flow\Migration\Exclusion\Service\AbstractExclusionService;
use Bitrix\Tasks\Flow\Migration\Strategy\Type\MigrateToManual\ForceManualDistributorChange;

class ManuallyResponsibleExclusionService extends AbstractExclusionService
{
	protected function getStrategySequence(): array
	{
		return [
			new ForceManualDistributorChange(),
		];
	}
}
