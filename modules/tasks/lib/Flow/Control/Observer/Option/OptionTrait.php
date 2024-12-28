<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;

trait OptionTrait
{
	private function hasManualDistributor(): bool
	{
		return
			$this->flowEntity->getDistributionType() === FlowDistributionType::MANUALLY->value
			&& isset($this->command->responsibleList[0])
			&& $this->command->responsibleList[0] > 0
		;
	}

	private function hasResponsibleQueue(): bool
	{
		return
			$this->flowEntity->getDistributionType() === FlowDistributionType::QUEUE->value
			&& !empty($this->command->responsibleList)
		;
	}

	private function hasResponsibleHimself(): bool
	{
		return
			$this->flowEntity->getDistributionType() === FlowDistributionType::HIMSELF->value
			&& !empty($this->command->responsibleList)
		;
	}
}