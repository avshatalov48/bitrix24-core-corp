<?php

namespace Bitrix\Crm\Order\TradingPlatform;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class RealizationDocument extends Platform
{
	const TRADING_PLATFORM_CODE = 'realization_document';

	/**
	 * @return string
	 */
	protected function getName(): string
	{
		return Main\Localization\Loc::getMessage('CRM_ORDER_TRADING_PLATFORM_REALIZATION_DOCUMENT');
	}
}
