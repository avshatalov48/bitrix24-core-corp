<?php

namespace Bitrix\Salescenter\Builder;

use Bitrix\Crm\Order\Builder\BasketBuilderWithDistributedQuantityControl;
use Bitrix\Crm\Order\Builder\SettingsContainer;
use Bitrix\Main;

/**
 * Class Manager
 * @package Bitrix\Salescenter\Builder
 */
final class Manager
{
	/**
	 * @param string $scenario
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getBuilder(string $scenario = ''): OrderBuilder
	{
		if ($scenario === SettingsContainer::BUILDER_SCENARIO_SHIPMENT)
		{
			return self::getShipmentScenarioBuilder();
		}
		elseif ($scenario === SettingsContainer::BUILDER_SCENARIO_PAYMENT)
		{
			return self::getPaymentScenarioBuilder();
		}
		elseif ($scenario === SettingsContainer::BUILDER_SCENARIO_RESERVATION)
		{
			return self::getReservationScenarioBuilder();
		}

		return self::getDefaultScenarioBuilder();
	}

	/**
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getPaymentScenarioBuilder(): OrderBuilder
	{
		return self::getOrderBuilder(
			[
				'createDefaultShipmentIfNeed' => false,
			]
		);
	}

	/**
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getDefaultScenarioBuilder(): OrderBuilder
	{
		return self::getOrderBuilder();
	}

	/**
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getShipmentScenarioBuilder(): OrderBuilder
	{
		return self::getOrderBuilder(
			[
				'createDefaultPaymentIfNeed' => false,
				'builderScenario' => SettingsContainer::BUILDER_SCENARIO_SHIPMENT
			]
		);
	}

	/**
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getReservationScenarioBuilder(): OrderBuilder
	{
		return self::getOrderBuilder(
			[
				'createDefaultShipmentIfNeed' => false,
				'createDefaultPaymentIfNeed' => false,
				'clearReservesIfEmpty' => true,
				'builderScenario' => SettingsContainer::BUILDER_SCENARIO_RESERVATION
			],
			\Bitrix\Sale\Helpers\Order\Builder\BasketBuilderSale::class
		);
	}

	/**
	 * @param array $settings
	 * @param string|null $basketBuilderClass
	 * @return OrderBuilder
	 */
	private static function getOrderBuilder(
		array $settings = [],
		string $basketBuilderClass = null
	): OrderBuilder
	{
		$builder = new OrderBuilder(new SettingsContainer($settings));

		$basketBuilderClass ??= BasketBuilderWithDistributedQuantityControl::class;

		$builder->setBasketBuilder(
			new $basketBuilderClass($builder)
		);

		return $builder;
	}
}
