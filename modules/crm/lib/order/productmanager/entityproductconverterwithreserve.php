<?php

namespace Bitrix\Crm\Order\ProductManager;

class EntityProductConverterWithReserve implements ProductConverter
{
	/**
	 * @param array $product
	 * @return array
	 */
	public function convertToSaleBasketFormat(array $product): array
	{
		$result = [
			'NAME' => $product['PRODUCT_NAME'],
			'MODULE' => $product['PRODUCT_ID'] ? 'catalog' : '',
			'PRODUCT_ID' => $product['PRODUCT_ID'],
			'OFFER_ID' => $product['PRODUCT_ID'],
			'QUANTITY' => $product['QUANTITY'],
			'BASE_PRICE' => $product['PRICE_NETTO'],
			'PRICE' => $product['PRICE'],
			'PRICE_EXCLUSIVE' => $product['PRICE_EXCLUSIVE'],
			'CUSTOM_PRICE' => 'Y',
			'DISCOUNT_SUM' => $product['DISCOUNT_SUM'],
			'DISCOUNT_RATE' => $product['DISCOUNT_RATE'],
			'DISCOUNT_TYPE_ID' => $product['DISCOUNT_TYPE_ID'],
			'MEASURE_CODE' => $product['MEASURE_CODE'],
			'MEASURE_NAME' => $product['MEASURE_NAME'],
			'TAX_RATE' => $product['TAX_RATE'],
			'TAX_INCLUDED' => $product['TAX_INCLUDED'],
		];

		if (isset($product['RESERVE_QUANTITY'], $product['STORE_ID']))
		{
			$reserveId = isset($product['RESERVE_ID']) && !empty($product['RESERVE_ID']) ? $product['RESERVE_ID'] : 'n1';
			$result['RESERVE'][$reserveId] = [
				'QUANTITY' => $product['RESERVE_QUANTITY'],
				'STORE_ID' => $product['STORE_ID'],
				'DATE_RESERVE_END' => $product['DATE_RESERVE_END'],
				'RESERVED_BY' => \CCrmSecurityHelper::GetCurrentUser()->GetID(),
			];
		}

		return $result;
	}
}
