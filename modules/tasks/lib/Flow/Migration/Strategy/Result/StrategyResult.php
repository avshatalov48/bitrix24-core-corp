<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Result;

use Bitrix\Main\Result;

class StrategyResult extends Result
{
	private bool $isFlowChanged = false;

	public function isFlowChanged(): bool
	{
		return $this->isFlowChanged;
	}

	public function setFlowChanged(bool $isFlowChanged = true): self
	{
		$this->isFlowChanged = $isFlowChanged;

		return $this;
	}

	public function isStrategyApplied(): bool
	{
		return $this->isFlowChanged() || $this->getErrors() !== [];
	}
}
