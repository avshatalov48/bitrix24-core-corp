<?php

namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\Item;
use Bitrix\Main\Result;

abstract class OperationFacade
{
	abstract public function add(Item $item): Result;

	public function restore(Item $item): Result
	{
		return $this->add($item);
	}

	abstract public function update(Item $itemBeforeSave, Item $item): Result;

	abstract public function delete(Item $itemBeforeDeletion): Result;
}
