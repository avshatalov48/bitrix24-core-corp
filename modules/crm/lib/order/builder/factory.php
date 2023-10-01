<?

namespace Bitrix\Crm\Order\Builder;

use Bitrix\Sale\Helpers\Order\Builder\BasketBuilderSale;
use Bitrix\Sale\Helpers\Order\Builder\OrderBuilder;

/**
 * Factory for creating order builders.
 */
final class Factory
{
	/**
	 * Builder with default settings.
	 *
	 * @return OrderBuilder
	 */
	public static function createDefaultBuilder(): OrderBuilder
	{
		return self::createOrderBuilder();
	}

	/**
	 * Builder for work with shipments.
	 *
	 * @return OrderBuilder
	 */
	public static function createBuilderForShipment(): OrderBuilder
	{
		return self::createOrderBuilder(
			[
				'createDefaultPaymentIfNeed' => false,
				'fillShipmentsByBasketBuilder' => true,
				'builderScenario' => SettingsContainer::BUILDER_SCENARIO_SHIPMENT
			],
			BasketBuilderWithDistributedQuantityControl::class,
			OrderBuilderCrm::class
		);
	}

	/**
	 * Builder for work with shipments.
	 *
	 * @return OrderBuilder
	 */
	public static function createBuilderForPayment(): OrderBuilder
	{
		return self::createOrderBuilder(
			[
				'createDefaultShipmentIfNeed' => false,
				'builderScenario' => SettingsContainer::BUILDER_SCENARIO_PAYMENT
			]
		);
	}

	/**
	 * Builder for work with reservations.
	 *
	 * @return OrderBuilder
	 */
	public static function createBuilderForReservation(): OrderBuilder
	{
		return self::createOrderBuilder(
			[
				'createDefaultShipmentIfNeed' => false,
				'createDefaultPaymentIfNeed' => false,
				'clearReservesIfEmpty' => true,
				'fillShipmentsByBasketBuilder' => true,
				'builderScenario' => SettingsContainer::BUILDER_SCENARIO_RESERVATION
			],
			BasketBuilderSale::class,
			OrderBuilderCrm::class
		);
	}

	/**
	 * Create builder for settings.
	 *
	 * @param array $settings
	 * @param string|null $basketBuilderClass
	 * @param string|null $orderBuilderClass
	 *
	 * @return OrderBuilder
	 */
	private static function createOrderBuilder(array $settings = [], ?string $basketBuilderClass = null, ?string $orderBuilderClass = null): OrderBuilder
	{
		$defaultSettings = [
			'createUserIfNeed' => SettingsContainer::SET_ANONYMOUS_USER,
			'deleteBasketItemsIfNotExists' => false,
			'deleteTradeBindingIfNotExists' => false,
			'acceptableErrorCodes' => [],
			'cacheProductProviderData' => true,
		];
		$settings = array_merge($defaultSettings, $settings);

		$basketBuilderClass ??= BasketBuilderCrm::class;
		$orderBuilderClass ??= OrderBuilderCrm::class;

		$builder = new $orderBuilderClass(
			new SettingsContainer($settings)
		);
		$builder->setBasketBuilder(
			new $basketBuilderClass($builder)
		);

		return $builder;
	}
}
