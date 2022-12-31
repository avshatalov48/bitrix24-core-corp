<?php

namespace Bitrix\Crm\Reservation\Compatibility;

use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Localization\Loc;
use CCrmProductRow;

Loc::loadLanguageFile(__FILE__);

/**
 * Processor of product row reserves.
 *
 * @internal this class is only needed for compatibility, so you need to use `Bitrix\Crm\Service\Sales\Reservation\ReservationService` for reserves.
 */
class ProductRowReserves
{
	/**
	 * Reservations the product rows.
	 *
	 * For correct work, need that product rows contain id.
	 * Inside reads the original rows, therefore before call this method need call `CCrmProductRow::setPerRowInsert`.
	 * Example:
	 * ```php
		try
		{
			\CCrmProductRow::setPerRowInsert(true);
			$result = \CCrmDeal::SaveProductRows($id, $productRows);
		}
		finally
		{
			\CCrmProductRow::setPerRowInsert(false);
		}

		if ($result)
		{
			\Bitrix\Crm\Reservation\Compatibility\ProductRowReserves::processRows($id, $productRows);
		}
	 * ```
	 *
	 * @param int $dealId
	 * @param array $productRows
	 *
	 * @return void
	 */
	public static function processRows(int $dealId, array $productRows): void
	{
		if (empty($productRows))
		{
			return;
		}

		// read original rows for filled row id
		$originalRows = CCrmProductRow::getOriginalRows();
		if ($originalRows)
		{
			$productRows = array_map(static function($row) {
				$result = $row['ORIGINAL_ROW'];
				$result['ID'] = $row['ID'];

				return $result;
			}, $originalRows);
		}

		ReservationService::getInstance()->reservationProductsByDealProductRows($dealId, $productRows);
	}
}
