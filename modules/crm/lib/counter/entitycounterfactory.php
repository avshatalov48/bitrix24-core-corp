<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\Integration\Intranet\CustomSectionProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Factory\Dynamic;
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
		if($code === self::TOTAL_COUNTER) {
			return self::createTotalCounter($code, $userID);
		}

		if($code === self::NO_ORDERS_COUNTER) {
			return self::createNoOrdersCounter($code, $userID);
		}

		if (CustomSectionProvider::isCustomSectionCounter($code))
		{
			return self::createCustomSectionCounter($code, $userID);
		}

		if (CustomSectionProvider::isCustomSectionPageCounter($code))
		{
			return self::createCustomPageCounter($code, $userID);
		}

		return null;
	}

	static public function create($entityTypeID, $typeID, $userID = 0, array $extras = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException("entityTypeID: $entityTypeID",
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

	private static function createCustomSectionCounter(string $code, int $userID): AggregateCounter
	{
		$settings = CustomSectionProvider::getPagesSettingsByCustomSectionCounterId($code);
		$data = [];
		foreach ($settings as $setting)
		{
			$entityTypeID = CustomSectionProvider::getEntityTypeByPageSetting($setting);

			if(!\CCrmOwnerType::IsDefined($entityTypeID))
			{
				continue;
			}

			$data[] = [
				'entityTypeID' => CustomSectionProvider::getEntityTypeByPageSetting($setting),
				'counterTypeID' => EntityCounterType::ALL,
			];
		}

		return new AggregateCounter($code, $data, $userID);
	}

	private static function createCustomPageCounter(string $code, int $userID): AggregateCounter
	{
		return new AggregateCounter($code, [
			[
				'entityTypeID' => CustomSectionProvider::getEntityTypeIdByCounterId($code),
				'counterTypeID' => EntityCounterType::ALL,
			],
		], $userID);
	}

	public static function createCallTrackerCounter(int $userId = 0): EntityCounter
	{
		return new CallTrackerActivityCounter(\Bitrix\Crm\Counter\EntityCounterType::CURRENT, $userId);
	}

	private static function createTotalCounter(string $code, int $userId): AggregateCounter
	{
		return new AggregateCounter(
			$code,
			array_map(
				fn (Factory $factory) => [
					'entityTypeID' => $factory->getEntityTypeId(),
					'counterTypeID' => EntityCounterType::ALL,
				],
				self::applyFilters(self::getEnabledFactories(), [
					[self::class, 'withoutCustomSectionsFilter'],
				])
			),
			$userId,
		);
	}

	private static function createNoOrdersCounter(string $code, int $userId): AggregateCounter
	{
		return new AggregateCounter(
			$code,
			array_map(
				fn (Factory $factory) => [
					'entityTypeID' => $factory->getEntityTypeId(),
					'counterTypeID' => EntityCounterType::ALL,
				],
				self::applyFilters(self::getEnabledFactories(), [
					[self::class, 'noOrdersFilter'],
					[self::class, 'withoutCustomSectionsFilter'],
				])
			),
			$userId,
		);
	}

	private static function applyFilters(array $factories, array $filters): array
	{
		return array_reduce($filters, [self::class, 'applyFilter'], $factories);
	}

	private static function applyFilter(array $factories, callable $filter): array
	{
		return array_filter($factories, $filter);
	}

	private static function noOrdersFilter(Factory $factory): bool {
		return $factory->getEntityTypeId() !== \CCrmOwnerType::Order;
	}

	private static function withoutCustomSectionsFilter(Factory $factory): bool
	{
		return !($factory instanceof Dynamic && CustomSectionProvider::hasCustomSection($factory));
	}

	private static function getEnabledFactories(): array
	{
		return array_filter(
			Container::getInstance()->getTypesMap()->getFactories(),
			fn (Factory $factory) => $factory->isCountersEnabled()
		);
	}
}