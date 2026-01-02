<?php

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Crm\Copilot\Restriction\LimitManager;
use Bitrix\Main\Loader;
use CBitrix24;

final class CostManager
{
	public static function isSponsoredOperation(): bool
	{
		return Loader::includeModule('bitrix24')
			&& CBitrix24::isLicensePaid()
			&& !CBitrix24::IsNfrLicense()
			&& !LimitManager::getInstance()->isPeriodLimitExceeded()
		;
	}
}
