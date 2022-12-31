<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Reservation;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;

class ProductRowReservationFactory
{
	/**
	 * Create, if can, product row reservation.
	 *
	 * @param ProductRow $productRow
	 * @param array $fields
	 *
	 * @return Reservation\ProductRowReservation|null
	 */
	public static function createFromArray(ProductRow $productRow, array $fields): ?Reservation\ProductRowReservation
	{
		if (!array_key_exists(ProductRowReservation::ROW_ID, $fields))
		{
			$rowId = $productRow->getId();
			if ($rowId)
			{
				$fields[ProductRowReservation::ROW_ID] = $rowId;
			}
		}

		if (self::canCreateProductReservation($productRow, $fields))
		{
			return Reservation\ProductRowReservation::create($productRow, $fields);
		}

		return null;
	}

	/**
	 * Checking whether it is possible to create a product reservation.
	 *
	 * @param ProductRow $productRow
	 * @param array $fields
	 *
	 * @return bool
	 */
	private static function canCreateProductReservation(ProductRow $productRow, array $fields): bool
	{
		$isNonCatalogProduct = empty($productRow->getProductId());
		if ($isNonCatalogProduct)
		{
			return false;
		}

		if (
			isset($fields['TYPE'])
			&& ReservationService::getInstance()->isRestrictedType((int)$fields['TYPE'])
		)
		{
			return false;
		}

		$canCreateOrLoad =
			isset($fields[ProductRowReservation::ROW_ID])
			|| isset($fields[ProductRowReservation::RESERVE_STORE_ID])
		;
		if (!$canCreateOrLoad)
		{
			return false;
		}

		$existAnyField =
			array_key_exists(ProductRowReservation::RESERVE_QUANTITY, $fields)
			|| array_key_exists(ProductRowReservation::RESERVE_STORE_ID, $fields)
			|| array_key_exists(ProductRowReservation::RESERVE_DATE_END, $fields)
		;

		return $existAnyField;
	}
}
