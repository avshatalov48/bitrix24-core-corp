<?php

namespace Bitrix\Intranet\Counters\Synchronizations;

use Bitrix\Intranet\Counters\ChainSyncInterface;
use Bitrix\Intranet\Counters\Counter;

abstract class AbstractSynchronization implements ChainSyncInterface
{
	private ?ChainSyncInterface $next = null;

	abstract public function sync(Counter $counter): void;

	public function setNext(ChainSyncInterface $next): void
	{
		$this->next = $next;
	}

	public function next(): ?ChainSyncInterface
	{
		return $this->next;
	}
}