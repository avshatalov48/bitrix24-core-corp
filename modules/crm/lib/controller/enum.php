<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Main\Result;
use Bitrix\Rest\Integration\View\Attributes;

class Enum extends Controller
{
	/**
	 * @return array
	 */
	public function getOrderOwnerTypesAction(): array
	{
		$paymentDependantEntityTypeIds = [];

		$relationManager = \Bitrix\Crm\Service\Container::getInstance()->getRelationManager();
		$orderParentRelations = $relationManager->getParentRelations(\CCrmOwnerType::Order);
		foreach ($orderParentRelations as $relation)
		{
			$parentEntityTypeId = $relation->getParentEntityTypeId();
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($parentEntityTypeId);
			if ($factory && $factory->isPaymentsEnabled())
			{
				$paymentDependantEntityTypeIds[] = [
					'ID' => $factory->getEntityTypeId(),
					'CODE' => \CCrmOwnerType::ResolveName($factory->getEntityTypeId()),
					'NAME' => \CCrmOwnerType::GetDescription($factory->getEntityTypeId()),
					'ATTRIBUTE'=> Attributes::DYNAMIC
				];
			}
		}

		return $paymentDependantEntityTypeIds;
	}

	protected function checkPermissionEntity($name, $arguments=[]): Result
	{
		return new Result();
	}
}