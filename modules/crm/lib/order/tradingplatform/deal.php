<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Deal
 * @package Bitrix\Sale\TradingPlatform\Landing
 */
class Deal extends Platform
{
	const TRADING_PLATFORM_CODE = 'deal';

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return Main\Localization\Loc::getMessage('CRM_ORDER_TRADING_PLATFORM_DEAL');
	}
}
