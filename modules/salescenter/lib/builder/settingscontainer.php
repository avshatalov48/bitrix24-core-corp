<?
namespace Bitrix\Salescenter\Builder;

use Bitrix\Sale\Helpers\Order;

class SettingsContainer extends Order\Builder\SettingsContainer
{
	public const BUILDER_SCENARIO_SHIPMENT = 'shipment';
	public const BUILDER_SCENARIO_PAYMENT = 'payment';
	public const BUILDER_SCENARIO_RESERVATION = 'reservation';

	protected function getDefaultSettings(): array
	{
		$settings = parent::getDefaultSettings();

		$settings['createUserIfNeed'] = SettingsContainer::SET_ANONYMOUS_USER;
		$settings['deleteBasketItemsIfNotExists'] = false;
		$settings['deleteTradeBindingIfNotExists'] = false;
		$settings['acceptableErrorCodes'] = [];
		$settings['cacheProductProviderData'] = true;
		$settings['builderScenario'] = self::BUILDER_SCENARIO_PAYMENT;

		return $settings;
	}
}
