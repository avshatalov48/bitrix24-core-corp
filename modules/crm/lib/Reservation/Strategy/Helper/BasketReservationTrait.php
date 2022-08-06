<?php

namespace Bitrix\Crm\Reservation\Strategy\Helper;

use Bitrix\Crm\Reservation\Internals\ProductReservationMapTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\BasketReserve;
use Bitrix\Crm\Service\Sale\BasketService;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Sale\Reservation\Internals\BasketReservationTable;

/**
 * Trait with functions of basket reserves.
 */
trait BasketReservationTrait
{
	/**
	 * Get basket reserves by product rows ids.
	 *
	 * @param array $rowsIds
	 *
	 * @return BasketReserve[]
	 */
	protected function getBasketReservesByRowsIds(array $rowsIds): array
	{
		$result = [];

		$basketReserves = array_map(fn($productId) => ['ID' => $productId], $rowsIds);
		$basketReserves = ReservationService::getInstance()->fillBasketReserves($basketReserves);
		$basketReserves = array_column($basketReserves, null, 'ID');

		$row2basket = BasketService::getInstance()->getRowIdsToBasketIdsByRows($rowsIds);
		foreach ($row2basket as $rowId => $basketId)
		{
			$item = new BasketReserve($basketId);

			$basketReserve = $basketReserves[$rowId] ?? null;
			if ($basketReserve)
			{
				$item->reserveId = $basketReserve['RESERVE_ID'];
				$item->quantity = $basketReserve['RESERVE_QUANTITY'];
			}

			$result[$rowId] = $item;
		}

		return $result;
	}

	/**
	 * Save basket reserve.
	 *
	 * @param array $fields
	 *
	 * @return UpdateResult|AddResult
	 */
	protected function saveBasketReserve(array $fields)
	{
		$id = $fields['ID'] ?? null;
		if (isset($id))
		{
			unset($fields['ID']);

			return BasketReservationTable::update($id, $fields);
		}

		return BasketReservationTable::add($fields);
	}

	/**
	 * Save reserves relation.
	 *
	 * @param int $productRowId
	 * @param int $basketReserveId
	 *
	 * @return UpdateResult|AddResult
	 */
	protected function saveReservesRelation(int $productRowId, int $basketReserveId)
	{
		$exist = ProductReservationMapTable::getRow([
			'filter' => [
				'=PRODUCT_ROW_ID' => $productRowId,
				'=BASKET_RESERVATION_ID' => $basketReserveId,
			],
		]);
		if (!$exist)
		{
			return ProductReservationMapTable::add([
				'PRODUCT_ROW_ID' => $productRowId,
				'BASKET_RESERVATION_ID' => $basketReserveId,
			]);
		}

		$result = new UpdateResult();
		$result->setPrimary($exist['ID']);

		return $result;
	}
}
