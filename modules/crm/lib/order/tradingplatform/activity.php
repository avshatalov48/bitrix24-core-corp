<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Activity
 * @package Bitrix\Crm\TradingPlatform
 */
class Activity
	extends Platform
	implements Sale\TradingPlatform\IRestriction
{
	const TRADING_PLATFORM_CODE = 'activity';

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return Main\Localization\Loc::getMessage('CRM_ORDER_TRADING_PLATFORM_ACTIVITY');
	}
}
