<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\ProductRowCollection;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * A trait to check the available quantity before deduction the entity items.
 */
trait QuantityCheckerTrait
{
	/**
	 * Checks quantity of products rows for deduction.
	 *
	 * Depending on the condition of the product, different quantity checks occur:
	 * 1. if new product row - the available quantity is the stock balance minus ALL current reserves;
	 * 2. if exist product row - the available quantity is calculated based on the reservation history;
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param array $productRows must contain fields: STORE_ID, QUANTITY, ID (optional TYPE)
	 *
	 * @return Result
	 */
	protected static function checkQuantityFromArray(int $ownerTypeId, int $ownerId, array $productRows): Result
	{
		$result = new Result();

		if (empty($productRows))
		{
			return $result;
		}

		$reservationService = ReservationService::getInstance();
		$defaultStore = (int)StoreTable::getDefaultStoreId();

		$productRowDeductedQuantities = [];
		if ($ownerId > 0)
		{
			$productRowDeductedQuantities = Container::getInstance()->getShipmentProductService()->getShippedQuantityByEntity(
				$ownerTypeId,
				$ownerId
			);
		}

		$availableQuantityCalculator = new AvailableQuantityCalculator();

		/** @var array $productRow */
		foreach ($productRows as $productRow)
		{
			$productRowId = (int)($productRow['ID'] ?? 0);
			$productId = (int)($productRow['PRODUCT_ID'] ?? 0);

			$typeId = (int)($productRow['TYPE'] ?? 0);
			if ($reservationService->isRestrictedType($typeId))
			{
				continue;
			}

			$quantity = (float)($productRow['QUANTITY'] ?? 0.0);
			$quantity -= (float)($productRowDeductedQuantities[$productRowId] ?? 0.0);
			if ($quantity <= 0.0)
			{
				continue;
			}

			$storeId = (int)($productRow['STORE_ID'] ?? 0);
			if ($storeId <= 0)
			{
				$storeId = $defaultStore;
			}

			$availableQuantityCalculator->addProductRow($productRowId, $productId, $storeId, $quantity);
		}

		$missingQuantities = $availableQuantityCalculator->getMissingQuantities();
		foreach ($missingQuantities as $item)
		{
			if (isset($item->id))
			{
				$result->addError(
					new Error("For product row with id '{$item->id}' quantity in shipment more than store")
				);
			}
			else
			{
				$result->addError(
					new Error("For product with id '{$item->productId}' quantity in shipment more than store")
				);
			}
		}

		return $result;
	}

	/**
	 * Checks quantity of products rows for deduction.
	 *
	 * @see ::checkQuantityFromArray for details.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param ProductRowCollection $productRows
	 *
	 * @return Result
	 */
	protected static function checkQuantityFromCollection(int $ownerTypeId, int $ownerId, ProductRowCollection $productRows): Result
	{
		$rows = [];

		$defaultStore = StoreTable::getDefaultStoreId();

		/** @var ProductRow $productRow */
		foreach ($productRows as $productRow)
		{
			$row = $productRow->toArray();

			$productRowReservation = $productRow->getProductRowReservation();
			if ($productRowReservation && $productRowReservation->getStoreId() > 0)
			{
				$row['STORE_ID'] = $productRowReservation->getStoreId();
			}
			else
			{
				$row['STORE_ID'] = $defaultStore;
			}

			$rows[] = $row;
		}

		return self::checkQuantityFromArray($ownerTypeId, $ownerId, $rows);
	}
}
