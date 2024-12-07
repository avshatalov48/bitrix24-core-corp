<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Model\RoleIndustryTable;

class RoleIndustrySync extends BaseSync
{
	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): RoleIndustryTable
	{
		return $this->dataManager ?? ($this->dataManager = new RoleIndustryTable());
	}
}
