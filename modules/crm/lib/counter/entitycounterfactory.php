<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;

class EntityCounterFactory
{
	const TOTAL_COUNTER = 'crm_all';
	public const NO_ORDERS_COUNTER = 'crm_all_no_orders';
	static public function isEntityTypeSupported($entityTypeID)
	{
		$factory = Container::getInstance()->getFactory((int)$entityTypeID);

		return ($factory && $factory->isCountersEnabled());
	}

	static public function createNamed($code, $userID = 0)
	{
		if (!in_array(
			$code,
			[
				self::TOTAL_COUNTER,
				self::NO_ORDERS_COUNTER,
			]
		))
		{
			return null;
		}

		$items = [];
		foreach (Container::getInstance()->getTypesMap()->getFactories() as $factory)
		{
			if ($factory->isCountersEnabled())
			{
				if (
					$code === self::NO_ORDERS_COUNTER
					&& $factory->getEntityTypeId() === \CCrmOwnerType::Order
				)
				{
					continue;
				}

				$items[] = [
					'entityTypeID' => $factory->getEntityTypeId(),
					'counterTypeID' => EntityCounterType::ALL,
				];
			}
		}

		return new AggregateCounter($code, $items, $userID);
	}

	static public function create($entityTypeID, $typeID, $userID = 0, array $extras = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			return new DealCounter($typeID, $userID, $extras);
		}
		elseif($entityTypeID === \CCrmOwnerType::Activity)
		{
			return new ActivityCounter($typeID, $userID, $extras);
		}

		return new EntityCounter($entityTypeID, $typeID, $userID, $extras);
	}

	public static function createCallTrackerCounter(int $userId = 0): EntityCounter
	{
		return new CallTrackerActivityCounter(\Bitrix\Crm\Counter\EntityCounterType::CURRENT, $userId);
	}
}