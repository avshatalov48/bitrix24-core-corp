<?php

namespace Bitrix\AI\Prompt;

use Bitrix\Main\Type\Dictionary;

class Collection extends Dictionary
{
	/**
	 * @var Item[]
	 */
	protected $values = [];

	/**
	 * Return the current element.
	 */
	#[\ReturnTypeWillChange]
	public function current(): Item
	{
		return current($this->values);
	}

	/**
	 * Pushes new Item to the end of Collection.
	 *
	 * @param Item $item New Item.
	 * @return void
	 */
	public function push(Item $item): void
	{
		$this->values[] = $item;
	}
}
