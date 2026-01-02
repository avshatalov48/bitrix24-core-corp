<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config;

class RuntimeData
{
	private bool $isMovedToRecyclebin;

	public function __construct(
		bool $isMovedToRecyclebin = false
	)
	{
		$this->isMovedToRecyclebin = $isMovedToRecyclebin;
	}

	public function isMovedToRecyclebin(): bool
	{
		return $this->isMovedToRecyclebin;
	}

	public function setMovedToRecyclebin(bool $isMoved): static
	{
		$this->isMovedToRecyclebin = $isMoved;

		return $this;
	}
}