<?php

namespace Bitrix\SalesCenter\Component;

use Bitrix\Catalog\VatTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Tax\VatCalculator;

class VatRate
{
	/**
	 * Prepare tax prices for sale basket item (price saves without vat).
	 *
	 * @param array $basketItems in format for `BasketItem`
	 *
	 * @return array
	 *
	 * @see \Bitrix\Catalog\v2\Integration\JS\ProductForm\BasketItem
	 */
	public static function prepareTaxPrices(array $basketItems): array
	{
		Loader::requireModule('sale');
		Loader::requireModule('catalog');

		foreach ($basketItems as & $item)
		{
			if (isset($item['taxId']))
			{
				$vatRateRow = VatTable::getRowById((int)$item['taxId']);
				if (!$vatRateRow)
				{
					continue;
				}

				$vatRate = isset($vatRateRow['RATE']) ? (float)$vatRateRow['RATE'] : null;
				if ($vatRate > 0)
				{
					$isVatNotIncluded = ($item['taxIncluded'] ?? 'Y') === 'N';
					if ($isVatNotIncluded)
					{
						$vatCalculator = new VatCalculator($vatRate / 100);

						$fields = [
							'price',
							'priceExclusive',
						];
						foreach ($fields as $field)
						{
							if (isset($item[$field]))
							{
								$priceWithTax = (float)$item[$field];
								$priceWithoutTax = $vatCalculator->allocate($priceWithTax);

								$item[$field] = $priceWithoutTax;
							}
						}
					}
				}
			}
		}

		return $basketItems;
	}

	/**
	 * Calculated price with vat from basket item fields.
	 *
	 * @param array $basketFields
	 *
	 * @return float
	 */
	public static function getPriceWithTax(array $basketFields): float
	{
		Loader::requireModule('sale');

		$price = (float)($basketFields['PRICE'] ?? 0.0);
		$vatIncluded = ($basketFields['VAT_INCLUDED'] ?? 'Y') === 'Y';
		if (!$vatIncluded)
		{
			$vatRate = (float)($basketFields['VAT_RATE'] ?? 0.0);
			if ($vatRate > 0)
			{
				// price with tax
				$price = (new VatCalculator($vatRate))->accrue($price);
			}
		}

		return $price;
	}
}
