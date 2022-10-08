<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

/**
 * Builder and resolver `XML_ID` of basket item for product row.
 */
class ProductRowXmlId
{
	/**
	 * Get BASKET_ID from the XML_ID of the product row.
	 *
	 * @param string $xmlId
	 *
	 * @return int|null
	 */
	public static function getBasketIdFromXmlId(string $xmlId): ?int
	{
		if (empty($xmlId))
		{
			return null;
		}

		$re = '/^sale_basket_(\d+)/';
		if (preg_match($re, $xmlId, $m))
		{
			return (int)$m[1];
		}
		return null;
	}

	/**
	 * Get XML_ID from the BASKET_ID.
	 *
	 * @param int $basketId
	 *
	 * @return string|null
	 */
	public static function getXmlIdFromBasketId(int $basketId): ?string
	{
		if (!$basketId)
		{
			return null;
		}
		return "sale_basket_{$basketId}";
	}
}
