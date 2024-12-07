<?php

namespace Bitrix\AI\ThirdParty;

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
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->values as $item)
		{
			$result[] = [
				'id' => $item->getId(),
				'name' => $item->getName(),
				'code' => $item->getCode(),
				'category' => $item->getCategory(),
				'completionsUrl' => $item->getCompletionsUrl(),
				'date' => (string)$item->getCreatedDate(),
			];
		}

		return $result;
	}
}
