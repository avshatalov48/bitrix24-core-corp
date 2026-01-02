<?php

namespace Bitrix\AI\Prompt;

use Bitrix\Main\Type\Dictionary;

/**
 * @property Item[] $values
 */
class Collection extends Dictionary
{
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
