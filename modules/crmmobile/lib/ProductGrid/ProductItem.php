<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Item;

final class ProductItem
{
	public static function rebuild(ProductRow $source, array $mutations, Item $entity): ProductRow
	{
		$sourceFields = $source->toArray();
		$result = ProductRow::createFromArray(array_merge($sourceFields, $mutations));
		$entityResult = $result->isNew()
			? $entity->addToProductRows($result)
			: $entity->updateProductRow((int)$sourceFields['PRODUCT_ID'], $result->toArray());

		if (!$entityResult->isSuccess())
		{
			throw new \RuntimeException(implode(PHP_EOL, $entityResult->getErrorMessages()));
		}

		return $result;
	}
}
