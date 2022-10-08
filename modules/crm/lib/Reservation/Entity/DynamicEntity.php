<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;

class DynamicEntity extends Base
{
	protected function checkLoadedEntity(): Main\Result
	{
		$result = new Main\Result();

		$item = null;
		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory)
		{
			$item = $factory->getItem($this->ownerId);
		}

		if (!$item)
		{
			$result->addError(
				new Main\Error("Dynamic entity with type {$this->ownerTypeId} and id {$this->ownerId} not found")
			);
		}

		return $result;
	}

	public function loadEntityProducts(): array
	{
		$dynamicEntityProducts = [];

		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$dynamicEntity = $factory->getItem($this->ownerId);
			if ($dynamicEntity)
			{
				$productsList = $dynamicEntity->getProductRows();
				if ($productsList)
				{
					foreach ($productsList as $item)
					{
						$dynamicEntityProducts[$item->getId()] = $item->toArray();
					}
				}
			}
		}

		return $dynamicEntityProducts;
	}
}
