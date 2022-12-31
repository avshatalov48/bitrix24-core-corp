<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;

final class CheckProductsOnUpdate extends Base
{
	public function process(Crm\Item $item): Main\Result
	{
		$result = new Main\Result();

		if ($this->isMovedToSuccessfulStage($item))
		{
			$productRows = $item->getProductRows();
			if ($productRows)
			{
				$checkResult = self::checkQuantityFromCollection($item->getEntityTypeId(), $item->getId(), $productRows);
				if (!$checkResult->isSuccess())
				{
					Crm\Activity\Provider\StoreDocument::addProductActivity($item->getId());

					$result->addError(Crm\Reservation\Error\InventoryManagementError::create());
				}

				$checkResult = self::checkAvailabilityServices($productRows);
				if (!$checkResult->isSuccess())
				{
					Crm\Activity\Provider\StoreDocument::addServiceActivity($item->getId());

					$result->addError(Crm\Reservation\Error\AvailabilityServices::create());
				}
			}
		}

		return $result;
	}
}
