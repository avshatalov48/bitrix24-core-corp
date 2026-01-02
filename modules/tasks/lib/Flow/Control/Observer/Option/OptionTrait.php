<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Option;

use Bitrix\Tasks\Flow\Attribute\AccessCode;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;

trait OptionTrait
{
	private function hasManualDistributor(): bool
	{
		$typeIsManually = $this->flowEntity->getDistributionType() === FlowDistributionType::MANUALLY->value;

		$responsibleAccessCode = $this->command->responsibleList[0] ?? '';
		$isResponsibleCodeCorrect = (new AccessCode())->check($responsibleAccessCode);

		return $typeIsManually && $isResponsibleCodeCorrect;
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

	private function hasResponsibleImmutable(): bool
	{
		return
			$this->flowEntity->getDistributionType() === FlowDistributionType::IMMUTABLE->value
			&& !empty($this->command->responsibleList)
		;
	}
}