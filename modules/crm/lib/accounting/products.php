<?php

namespace Bitrix\Crm\Accounting;

use Bitrix\Crm\Accounting;
use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRow;

class Products
{
	/**
	 * @param Item $item
	 * @param ProductRow[] $products
	 *
	 * @return float
	 */
	public static function calculateSum(Item $item, array $products): float
	{
		$productRows = static::productsToProductRows($products);
		$personTypeId = Accounting::resolvePersonTypeId($item);
		$result = \CCrmSaleHelper::Calculate($productRows, $item->getCurrencyId(), $personTypeId);

		return $result['PRICE'];
	}

	/**
	 * @param Item $item
	 * @param ProductRow[] $products
	 *
	 * @return float
	 */
	public static function calculateTaxValue(Item $item, array $products): float
	{
		$productRows = static::productsToProductRows($products);
		$personTypeId = Accounting::resolvePersonTypeId($item);
		$result = \CCrmSaleHelper::Calculate($productRows, $item->getCurrencyId(), $personTypeId);

		return $result['TAX_VALUE'];
	}

	/**
	 * Transforms an array of ProductsRow object to an array of arrays, which is used in the old API
	 *
	 * @param ProductRow[] $products
	 *
	 * @return array
	 */
	protected static function productsToProductRows(array $products): array
	{
		$productRows = [];
		foreach ($products as $product)
		{
			$productRow = $product->collectValues();
			$productRow['TAX_INCLUDED'] = $product->getTaxIncluded() ? 'Y' : 'N';
			$productRow['CUSTOMIZED'] = $product->getCustomized() ? 'Y' : 'N';

			$productRows[] = $productRow;
		}

		return $productRows;
	}
}