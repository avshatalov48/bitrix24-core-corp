<?php

namespace Bitrix\Crm\Order;

/**
 * Class OrderEntityBindingsChecker
 * @package Bitrix\Crm\Order
 */
class OrderEntityBindingsChecker
{
	/**
	 * Checks order for bindings to payable entities
	 * @param int $orderId
	 * @return bool
	 */
	public static function hasBindingsWithPayableEntities(int $orderId): bool
	{
		$orderItemIdentifier = new \Bitrix\Crm\ItemIdentifier(\CCrmOwnerType::Order, $orderId);
		$orderRelations =
			\Bitrix\Crm\Service\Container::getInstance()
				->getRelationManager()
				->getParentElements($orderItemIdentifier)
		;
		if (empty($orderRelations))
		{
			return false;
		}

		foreach ($orderRelations as $orderRelation)
		{
			if (in_array($orderRelation->getEntityTypeId(), self::getPayableEntityTypeIds(), true))
			{
				return true;
			}
		}

		return false;
	}

	private static function getPayableEntityTypeIds(): array
	{
		static $requiredBoundEntityTypes = [];
		if (empty($requiredBoundEntityTypes))
		{
			foreach (\Bitrix\Crm\Service\Container::getInstance()->getTypesMap()->getFactories() as $crmFactory)
			{
				if ($crmFactory->isPaymentsEnabled())
				{
					$requiredBoundEntityTypes[] = $crmFactory->getEntityTypeId();
				}
			}
		}

		return $requiredBoundEntityTypes;
	}
}
