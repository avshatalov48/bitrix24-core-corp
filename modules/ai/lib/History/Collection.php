<?php

namespace Bitrix\AI\History;

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
			$itemData = [
				'id' => $item->getId(),
				'date' => (string)$item->getCreatedDate(),
				'data' => $item->getData(),
				'engineCode' => $item->getEngineCode(),
				'payload' => $item->getPayloadRawData(),
			];
			if ($item->isGrouped())
			{
				$itemData['groupData'] = $item->getGroupData();
			}
			$result[] = $itemData;
		}

		return $result;
	}
}
