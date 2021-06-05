<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;

class ProductRowCollection extends EO_ProductRow_Collection
{
	public function toArray(): array
	{
		$result = [];

		/** @var ProductRow $product */
		foreach ($this as $product)
		{
			$result[] = $product->collectValues(Values::ALL, FieldTypeMask::SCALAR);
		}

		return $result;
	}
}
