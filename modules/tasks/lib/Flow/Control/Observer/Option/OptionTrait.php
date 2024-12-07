<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Tasks\Flow\Flow;

trait OptionTrait
{
	private function hasManualDistributor(): bool
	{
		return
			$this->flowEntity->getDistributionType() === Flow::DISTRIBUTION_TYPE_MANUALLY
			&& $this->command->manualDistributorId > 0
		;
	}

	private function hasQueue(): bool
	{
		return
			$this->flowEntity->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE
			&& !empty($this->command->responsibleQueue)
		;
	}
}