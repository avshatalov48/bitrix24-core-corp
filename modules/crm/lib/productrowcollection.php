<?php

namespace Bitrix\Crm;

class ProductRowCollection extends EO_ProductRow_Collection
{
	/**
	 * Transform this collection into multidimensional array
	 *
	 * @return array[]
	 */
	public function toArray(): array
	{
		$result = [];

		/** @var ProductRow $product */
		foreach ($this as $product)
		{
			$result[] = $product->toArray();
		}

		return $result;
	}
}
