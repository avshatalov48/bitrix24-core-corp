<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Catalog\VatTable;
use Bitrix\Main\Loader;

Loader::requireModule('salescenter');

trait SalescenterControllerWrapper
{
	protected function prepareBasketItems(array $products): array
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

	protected function isTaxIncluded($items): bool
	{
		foreach ($items as $productRow)
		{
			if ($productRow['taxIncluded'] === 'Y')
			{
				return true;
			}
		}
		return false;
	}

	protected function isTaxPartlyIncluded($items): bool
	{
		$hasItemsWithTaxIncluded = null;
		$hasItemsWithNoTaxIncluded = null;

		foreach ($items as $productRow)
		{
			if (isset($productRow['taxIncluded']) && $productRow['taxIncluded'] === 'Y')
			{
				$hasItemsWithTaxIncluded = true;
			}
			elseif (isset($productRow['taxRate']) && $productRow['taxRate'] > 0)
			{
				$hasItemsWithNoTaxIncluded = true;
			}
		}

		return ($hasItemsWithNoTaxIncluded && $hasItemsWithTaxIncluded);
	}
}
