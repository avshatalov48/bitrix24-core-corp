<?php

namespace Bitrix\Crm\Order\ProductManager;

/**
 * Converter with reserve info.
 */
class EntityProductConverterWithReserve extends EntityProductConverter
{
	/**
	 * @inheritDoc
	 */
	public function convertToSaleBasketFormat(array $product): array
	{
		$result = parent::convertToSaleBasketFormat($product);

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

	/**
	 * @inheritDoc
	 */
	public function convertToCrmProductRowFormat(array $basketItem): array
	{
		// reserves are saved separately, we give the row as is.
		return parent::convertToCrmProductRowFormat($basketItem);
	}
}
