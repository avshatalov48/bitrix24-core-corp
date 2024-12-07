<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Model\PlanTable;

class PlanSync extends BaseSync
{
	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): PlanTable
	{
		return $this->dataManager ?? ($this->dataManager = new PlanTable());
	}
}
