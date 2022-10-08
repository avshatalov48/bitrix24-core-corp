<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

/**
 * Basket item XML_ID builder and resolver.
 */
class BasketXmlId
{
	/**
	 * Get ROW_ID from the XML_ID of the basket item.
	 *
	 * @param string $xmlId
	 *
	 * @return int|null
	 */
	public static function getRowIdFromXmlId(string $xmlId): ?int
	{
		if (empty($xmlId))
		{
			return null;
		}

		$re = '/^crm_pr_(\d+)/';
		if (preg_match($re, $xmlId, $m))
		{
			return (int)$m[1];
		}
		return null;
	}

	/**
	 * Get XML_ID from the ROW_ID crm product row.
	 *
	 * @param int $rowId
	 *
	 * @return string|null
	 */
	public static function getXmlIdFromRowId(int $rowId): ?string
	{
		if (!$rowId)
		{
			return null;
		}
		return "crm_pr_{$rowId}";
	}
}
