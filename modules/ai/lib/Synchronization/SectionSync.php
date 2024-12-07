<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Model\SectionTable;

class SectionSync extends BaseSync
{
	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): SectionTable
	{
		return $this->dataManager ?? ($this->dataManager = new SectionTable());
	}
}
