<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Notifications;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

class Availability
{
	public static function isAvailable(): bool
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return (bool)Option::get('booking', 'feature_booking_notifications_enabled', false);
		}

		return \CBitrix24::getPortalZone() === 'ru';
	}
}
