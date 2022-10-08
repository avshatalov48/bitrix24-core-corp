<?php

namespace Bitrix\Crm\Order\Builder;

class SettingsContainer extends \Bitrix\Sale\Helpers\Order\Builder\SettingsContainer
{
	protected function getDefaultSettings(): array
	{
		$settings = parent::getDefaultSettings();

		$settings['createUserIfNeed'] = self::SET_ANONYMOUS_USER;
		$settings['deleteBasketItemsIfNotExists'] = false;
		$settings['deleteTradeBindingIfNotExists'] = false;
		$settings['acceptableErrorCodes'] = [];
		$settings['cacheProductProviderData'] = true;
		$settings['fillShipmentsByBasketBuilder'] = false;
		$settings['builderScenario'] = self::BUILDER_SCENARIO_PAYMENT;

		return $settings;
	}
}
