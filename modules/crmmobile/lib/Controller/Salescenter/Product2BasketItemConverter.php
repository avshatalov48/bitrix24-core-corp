<?php

namespace Bitrix\CrmMobile\Controller\Salescenter;

use Bitrix\Catalog\VatTable;

class Product2BasketItemConverter
{
	public static function convert(array $products): array
	{
		$result = [];

		foreach ($products as $product)
		{
			$basePrice = null;
			$taxIncluded = $product['TAX_INCLUDED'];
			if (isset($product['BASE_PRICE']))
			{
				// Temporary hack for compatibility with web
				if ($product['TAX_INCLUDED'] === 'N' && $product['TAX_RATE'] > 0)
				{
					$basePrice = $product['PRICE'];
					$taxIncluded = 'Y';
				}
				else
				{
					$basePrice = $product['PRICE_BRUTTO'];
				}
			}
			elseif (isset($product['TAX_INCLUDED'], $product['PRICE_NETTO'], $product['PRICE_BRUTTO']))
			{
				if ($product['TAX_INCLUDED'] === 'N' && $product['TAX_RATE'] > 0)
				{
					$basePrice = $product['PRICE'];
					$taxIncluded = 'Y';
				}
				else
				{
					$basePrice = $product['PRICE_BRUTTO'];
				}
			}

			$item = [
				'innerId' => $product['BASKET_ITEM_FIELDS']['XML_ID'] ?? '',
				'module' => $product['BASKET_ITEM_FIELDS']['MODULE'] ?? '',
				'name' => $product['PRODUCT_NAME'],
				'skuId' => $product['PRODUCT_ID'],
				'sort' => $product['SORT'],
				'basePrice' => $basePrice,
				'price' => $product['PRICE'],
				'priceExclusive' => $product['PRICE'],
				'isCustomPrice' => 'Y',
				'discountType' => $product['DISCOUNT_TYPE_ID'],
				'quantity' => $product['QUANTITY'],
				'discountRate' => $product['DISCOUNT_RATE'],
				'discount' => $product['DISCOUNT_SUM'],
				'additionalFields' => [
					'originBasketId' => $product['BASKET_ITEM_FIELDS']['ADDITIONAL_FIELDS']['ORIGIN_BASKET_ID'] ?? '',
					'originProductId' => $product['BASKET_ITEM_FIELDS']['ADDITIONAL_FIELDS']['ORIGIN_PRODUCT_ID'] ?? '',
				],
				'taxIncluded' => $taxIncluded,
				'taxId' => VatTable::getActiveVatIdByRate((float)$product['TAX_RATE']),
				'type' => $product['TYPE'],
			];

			$basketCode = $product['BASKET_ITEM_FIELDS']['BASKET_CODE'] ?? '';
			if ($basketCode)
			{
				$item['code'] = $basketCode;
			}

			$result[] = $item;
		}

		return $result;
	}
}
