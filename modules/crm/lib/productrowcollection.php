<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Objectify\Values;

class ProductRowCollection extends EO_ProductRow_Collection
{
	/**
	 * Transform this collection into multidimensional array
	 *
	 * @return array[]
	 */
	public function toArray($valuesType = Values::ALL): array
	{
		$result = [];

		/** @var ProductRow $product */
		foreach ($this as $product)
		{
			$result[] = $product->toArray($valuesType);
		}

		return $result;
	}
}
