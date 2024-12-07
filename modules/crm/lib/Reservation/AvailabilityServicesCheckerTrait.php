<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Crm\ProductType;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

/**
 * A trait to check the availability of the service.
 */
trait AvailabilityServicesCheckerTrait
{
	/**
	 * Checking the services in the transmitted list of products for availability.
	 *
	 * @param array $productRows must contain fields: PRODUCT_ID, TYPE
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected static function checkAvailabilityServices(array $productRows): Result
	{
		$productIds = array_reduce($productRows, function ($productIds, $productRow) {
			$type = (int)($productRow['TYPE'] ?? 0);

			if ($type === ProductType::TYPE_SERVICE) {
				$productIds[] = $productRow['PRODUCT_ID'];
			}

			return $productIds;
		}, []);

		$result = new Result();

		if (!$productIds)
		{
			return $result;
		}

		$productIterator = ProductTable::getList([
			'select' => [
				'ID',
				'AVAILABLE',
			],
			'filter' => [
				'=ID' => $productIds,
			],
		]);
		while ($product = $productIterator->fetch())
		{
			if ($product['AVAILABLE'] === ProductTable::STATUS_NO)
			{
				$result->addError(
					new Error("Product with id {$product['ID']} is not available")
				);
			}
		}

		return $result;
	}
}
