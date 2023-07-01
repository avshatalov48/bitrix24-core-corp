<?php

namespace Bitrix\Market;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;

class Subscription
{
	public static function getFinishDate(): ?Date
	{
		$result = null;

		if (ModuleManager::isModuleInstalled('bitrix24')) {
			$timestamp = (int)Option::get('bitrix24', '~mp24_paid_date');
		} else {
			$timestamp = (int)Option::get('main', '~mp24_paid_date');
		}

		if ($timestamp > 0) {
			$result = Date::createFromTimestamp($timestamp);
		}

		return $result;
	}
}