<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use CCrmOwnerTypeAbbr;

Loc::loadMessages(__FILE__);

/**
 * Automatic reservation of the quantity equal to the quantity of product in the deal.
 * As soon as the user manually changes product quantity, the automation is disabled.
 */
class ReserveQuantityEqualProductQuantityStrategy implements Strategy
{
	public int $defaultStoreId;
	public ?Date $defaultDateReserveEnd;

	/**
	 * @inheritDoc
	 */
	public function reservation(int $ownerTypeId, int $ownerId): ReservationResult
	{
		$result = new ReservationResult();

		$rows = $this->getProductRows($ownerTypeId, $ownerId);
		foreach ($rows as $row)
		{
			$rowId = (int)$row['ID'];
			$quantity = (float)$row['QUANTITY'];
			if ($row['RESERVE_ID'])
			{
				$reserveInfo = $result->addReserveInfo(
					$rowId,
					$quantity,
					0
				);
				$reserveInfo->storeId = $row['RESERVE_STORE_ID'];
				$reserveInfo->dateReserveEnd = (string)$row['RESERVE_DATE_RESERVE_END'];

				if ($row['RESERVE_IS_AUTO'] !== 'Y')
				{
					continue;
				}

				$reserveQuantity = (float)$row['RESERVE_QUANTITY'];
				if ($reserveQuantity === $quantity)
				{
					continue;
				}

				$saveResult = $this->saveCrmReserve([
					'ID' => $row['RESERVE_ID'],
					'RESERVE_QUANTITY' => $quantity,
				]);

				$reserveInfo->deltaReserveQuantity = $quantity - $reserveQuantity;
			}
			else
			{
				$saveResult = $this->saveCrmReserve([
					'ROW_ID' => $rowId,
					'RESERVE_QUANTITY' => $quantity,
					'STORE_ID' => $this->defaultStoreId,
					'DATE_RESERVE_END' => $this->defaultDateReserveEnd,
					'IS_AUTO' => 'Y',
				]);

				$reserveInfo = $result->addReserveInfo(
					$rowId,
					$quantity,
					$quantity
				);
				$reserveInfo->storeId = $this->defaultStoreId;
				$reserveInfo->dateReserveEnd = (string)$this->defaultDateReserveEnd;
			}

			$result->addErrors($saveResult->getErrors());
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function reservationProductRow(int $productRowId, float $quantity, int $storeId, ?Date $dateReserveEnd): Result
	{
		$result = new Result();

		$productRow = $this->getProductRow($productRowId);
		if (!$productRow)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_RESERVE_QUANTITY_EQUAL_PRODUCT_QUANTITY_STRATEDY_PRODUCT_NOT_FOUND'))
			);
			return $result;
		}

		$productRowQuantity = (float)$productRow['QUANTITY'];
		if ($productRowQuantity < $quantity)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_RESERVE_QUANTITY_EQUAL_PRODUCT_QUANTITY_STRATEDY_LARGE_RESERVE_THAN_QUANTITY'))
			);
			return $result;
		}

		$isAutoReservation = $productRowQuantity === $quantity;

		$existReserve = $this->getReserve($productRowId);
		if ($existReserve)
		{
			return $this->saveCrmReserve([
				'ID' => $existReserve['ID'],
				'RESERVE_QUANTITY' => $quantity,
				'STORE_ID' => $storeId,
				'DATE_RESERVE_END' => $dateReserveEnd,
				'IS_AUTO' => $isAutoReservation && $existReserve['IS_AUTO'] === 'Y' ? 'Y' : 'N',
			]);
		}

		// If the quantity is empty, reserve all. Only when adding.
		if ($quantity === 0.0)
		{
			$quantity = $productRowQuantity;
			$isAutoReservation = true;
		}

		return $this->saveCrmReserve([
			'ROW_ID' => $productRowId,
			'RESERVE_QUANTITY' => $quantity,
			'STORE_ID' => $storeId,
			'DATE_RESERVE_END' => $dateReserveEnd,
			'IS_AUTO' => $isAutoReservation ? 'Y' : 'N',
		]);
	}

	/**
	 * Get products rows.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 *
	 * @return array
	 */
	protected function getProductRows(int $ownerTypeId, int $ownerId): array
	{
		return ProductRowTable::getList([
			'select' => [
				'ID',
				'QUANTITY',
				'RESERVE_ID' => 'RESERVATION.ID',
				'RESERVE_QUANTITY' => 'RESERVATION.RESERVE_QUANTITY',
				'RESERVE_IS_AUTO' => 'RESERVATION.IS_AUTO',
				'RESERVE_DATE_RESERVE_END' => 'RESERVATION.DATE_RESERVE_END',
				'RESERVE_STORE_ID' => 'RESERVATION.STORE_ID',
			],
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeId),
				'=OWNER_ID' => $ownerId,
			],
		])->fetchAll();
	}

	/**
	 * Get product row.
	 *
	 * @param int $rowId
	 *
	 * @return array|null
	 */
	protected function getProductRow(int $rowId): ?array
	{
		return ProductRowTable::getRow([
			'select' => [
				'ID',
				'QUANTITY',
			],
			'filter' => [
				'=ID' => $rowId,
			],
		]);
	}

	/**
	 * Save reserve.
	 *
	 * @param array $fields
	 *
	 * @return Result
	 */
	protected function saveCrmReserve(array $fields): Result
	{
		$id = $fields['ID'] ?? null;
		if (isset($id))
		{
			unset($fields['ID']);

			return ProductRowReservationTable::update($id, $fields);
		}

		return ProductRowReservationTable::add($fields);
	}

	/**
	 * Get reserve by row id.
	 *
	 * @param int $productRowId
	 *
	 * @return array|null
	 */
	protected function getReserve(int $productRowId): ?array
	{
		return ProductRowReservationTable::getRow([
			'select' => [
				'ID',
				'IS_AUTO',
			],
			'filter' => [
				'=ROW_ID' => $productRowId,
			],
		]);
	}
}
