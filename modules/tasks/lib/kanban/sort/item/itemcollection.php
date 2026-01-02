<?php

namespace Bitrix\Tasks\Kanban\Sort\Item;

use Bitrix\Main\Type\Dictionary;

/**
 * @property MenuItem[] $values
 */
class ItemCollection extends Dictionary
{
	public function add(MenuItem $item): static
	{
		$this->values[] = $item;
		return $this;
	}
}