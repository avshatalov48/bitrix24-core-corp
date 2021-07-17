<?php

namespace Bitrix\Salescenter\Builder;

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
	 * @param array $settings
	 * @return OrderBuilder
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private static function getOrderBuilder(array $settings = []): OrderBuilder
	{
		$builder = new OrderBuilder(new SettingsContainer($settings));

		$builder->setBasketBuilder(
			new BasketBuilder($builder)
		);

		return $builder;
	}
}
