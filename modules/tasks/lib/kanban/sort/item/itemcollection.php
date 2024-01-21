<?php

namespace Bitrix\Tasks\Kanban\Sort\Item;

use Bitrix\Main\Type\Dictionary;

class ItemCollection extends Dictionary
{
	/** @var MenuItem[] */
	protected $values = [];

	public function add(MenuItem $item): static
	{
		$this->values[] = $item;
		return $this;
	}
}