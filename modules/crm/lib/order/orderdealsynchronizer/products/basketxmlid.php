<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products;

/**
 * Basket item XML_ID builder and resolver.
 */
class BasketXmlId
{
	public const PREFIX = 'crm_pr_';

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

		$re = '/^'.self::PREFIX.'(\d+)/';
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
		return self::PREFIX.$rowId;
	}
}
