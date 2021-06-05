<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Item;
use Bitrix\Main\Result;

class CopyResult extends Result
{
	/** @var Item */
	protected $copy;

	public function setCopy(Item $copy): CopyResult
	{
		$this->copy = $copy;

		return $this;
	}

	public function getCopy(): ?Item
	{
		return $this->copy;
	}
}
