<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;
use CCrmOwnerType;

final class CheckProductsOnAdd extends Base
{
	public function process(Crm\Item $item): Main\Result
	{
		$result = new Main\Result();

		$factory = Crm\Service\Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return $result;
		}

		if ($this->isSuccessStage($item))
		{
			$productRows = $item->getProductRows();
			if ($productRows)
			{
				$checkResult = self::checkQuantityFromCollection(CCrmOwnerType::Deal, 0, $productRows);
				if (!$checkResult->isSuccess())
				{
					$stageId = $factory->setStartStageIdPermittedForUser($item);
					$item->setStageId($stageId);
				}

				$checkResult = self::checkAvailabilityServices($productRows);
				if (!$checkResult->isSuccess())
				{
					$stageId = $factory->setStartStageIdPermittedForUser($item);
					$item->setStageId($stageId);
				}
			}
		}

		return $result;
	}
}
