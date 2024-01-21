<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Traits\Singleton;

class AvailableRecoveriesFactory
{
	use Singleton;

	/**
	 * @return AsyncRecovery[]
	 */
	public function make(): array
	{
		return [
			new CountableCompleted(),
			new CountableDeleted(),
			new UncompletedCompleted(),
			new UncompletedDeleted(),
			new LightCounterCompleted(),
		];
	}
}