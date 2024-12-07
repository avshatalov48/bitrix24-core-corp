<?php

namespace Bitrix\AI\Tuning;

use Bitrix\Main\Type\Dictionary;

/**
 * Dictionary for work with Item objects
 */
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
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return Item | null
	 */
	public function get($name): ?Item
	{
		if (array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}
		return null;
	}

	/**
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$data = [];

		foreach ($this->values as $item)
		{
			$data[$item->getCode()] = $item->getValue();
		}

		return $data;
	}

	public function sort(): void
	{
		/**
		 * @return int - <0, 0 or >0 if 1st element less than, equal to, or greater than 2nd
		 */
		$compare = function(Item $itemA, Item $itemB) {
			if (
				$itemA->getSort() === $itemB->getSort()
			)
			{
				return strcasecmp($itemA->getCode(), $itemB->getCode());
			}

			if ($itemA->getSort() === null)
			{
				return 1;
			}

			if ($itemB->getSort() === null)
			{
				return -1;
			}

			return $itemA->getSort() > $itemB->getSort() ? 1 : -1;
		};

		uasort($this->values, $compare);
	}
}
