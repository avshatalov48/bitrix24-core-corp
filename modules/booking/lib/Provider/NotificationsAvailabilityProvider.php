<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

class NotificationsAvailabilityProvider
{
	public static function isAvailable(): bool
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return (bool)Option::get('booking', 'feature_booking_notifications_enabled', false);
		}

		return true;
	}
}
