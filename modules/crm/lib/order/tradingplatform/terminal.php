<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

class Terminal extends Platform implements Sale\TradingPlatform\IRestriction
{
	const TRADING_PLATFORM_CODE = 'terminal';

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return Main\Localization\Loc::getMessage('CRM_ORDER_TRADING_PLATFORM_TERMINAL');
	}
}
