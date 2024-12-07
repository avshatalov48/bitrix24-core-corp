<?php

namespace Bitrix\Crm\Reservation\Strategy;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\ProductType;
use Bitrix\Crm\Reservation\Internals\ProductRowReservationTable;
use Bitrix\Crm\Reservation\Strategy\Reserve\ReservationResult;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
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
				$reserveInfo->setStoreId($row['RESERVE_STORE_ID']);
				$reserveInfo->setDateReserveEnd((string)$row['RESERVE_DATE_RESERVE_END']);

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

				$reserveInfo->setDeltaReserveQuantity($quantity - $reserveQuantity);
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
				$reserveInfo->setStoreId($this->defaultStoreId);
				$reserveInfo->setDateReserveEnd((string)$this->defaultDateReserveEnd);
			}

			$result->addErrors($saveResult->getErrors());
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function reservationProductRow(int $productRowId, float $quantity, int $storeId, ?Date $dateReserveEnd): ReservationResult
	{
		$result = new ReservationResult();

		$productRow = $this->getProductRow($productRowId);
		if (!$productRow)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_RESERVE_QUANTITY_EQUAL_PRODUCT_QUANTITY_STRATEDY_PRODUCT_NOT_FOUND'))
			);
			return $result;
		}

		if (
			ReservationService::getInstance()->isRestrictedType((int)$productRow['TYPE'])
			|| (int)$productRow['PRODUCT_ID'] === 0
		)
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_RESERVATION_STRATEGY_RESERVE_QUANTITY_EQUAL_PRODUCT_QUANTITY_STRATEGY_PRODUCT_NOT_SUPPORT_RESERVATION'))
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

		$reserveInfo = $result->addReserveInfo(
			$productRowId,
			$quantity,
			$quantity
		);
		$reserveInfo->setStoreId($storeId);
		$reserveInfo->setDateReserveEnd($dateReserveEnd ? (string)$dateReserveEnd : null);

		$isAutoReservation = $productRowQuantity === $quantity;
		$existReserve = $this->getReserve($productRowId);
		if ($existReserve)
		{
			$existDateReserveEndFormatted = $existReserve['DATE_RESERVE_END']?->format('d.m.Y');
			$newDateReserveEndFormatted = $dateReserveEnd?->format('d.m.Y');

			if ($existDateReserveEndFormatted !== $newDateReserveEndFormatted)
			{
				$reserveInfo->setChanged();
			}

			if ((int)$existReserve['STORE_ID'] !== $storeId)
			{
				$reserveInfo->setChanged();
			}
			else
			{
				$reserveInfo->setDeltaReserveQuantity($quantity - (float)$existReserve['RESERVE_QUANTITY']);
			}

			$saveResult = $this->saveCrmReserve([
				'ID' => $existReserve['ID'],
				'RESERVE_QUANTITY' => $quantity,
				'STORE_ID' => $storeId,
				'DATE_RESERVE_END' => $dateReserveEnd,
				'IS_AUTO' => $isAutoReservation && $existReserve['IS_AUTO'] === 'Y' ? 'Y' : 'N',
			]);

			$result->addErrors(
				$saveResult->getErrors()
			);

			return $result;
		}

		// If the quantity is empty, reserve all. Only when adding.
		if ($quantity === 0.0)
		{
			$quantity = $productRowQuantity;
			$isAutoReservation = true;

			$reserveInfo->setReserveQuantity($quantity);
			$reserveInfo->setDeltaReserveQuantity($quantity);
		}

		$saveResult = $this->saveCrmReserve([
			'ROW_ID' => $productRowId,
			'RESERVE_QUANTITY' => $quantity,
			'STORE_ID' => $storeId,
			'DATE_RESERVE_END' => $dateReserveEnd,
			'IS_AUTO' => $isAutoReservation ? 'Y' : 'N',
		]);

		$result->addErrors(
			$saveResult->getErrors()
		);

		return $result;
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
				'RESERVE_ID' => ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME . '.ID',
				'RESERVE_QUANTITY' => ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME . '.RESERVE_QUANTITY',
				'RESERVE_IS_AUTO' => ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME . '.IS_AUTO',
				'RESERVE_DATE_RESERVE_END' => ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME . '.DATE_RESERVE_END',
				'RESERVE_STORE_ID' => ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME . '.STORE_ID',
			],
			'filter' => [
				'=OWNER_TYPE' => CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeId),
				'=OWNER_ID' => $ownerId,
				'!@TYPE' => ReservationService::getInstance()->getRestrictedProductTypes(),
				'!=PRODUCT_ID' => 0,
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
				'TYPE',
				'PRODUCT_ID',
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
				'STORE_ID',
				'RESERVE_QUANTITY',
				'DATE_RESERVE_END',
			],
			'filter' => [
				'=ROW_ID' => $productRowId,
			],
		]);
	}

	/**
	 * Checks product's type and returns true if type equals TYPE_SERVICE
	 *
	 * @param int $type
	 * @return bool
	 */
	private function isServiceProduct(int $type): bool
	{
		return $type === ProductType::TYPE_SERVICE;
	}
}
