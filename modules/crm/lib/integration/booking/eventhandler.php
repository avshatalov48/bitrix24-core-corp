<?php

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Integration\Booking\ClientProviderInterface;
use Bitrix\Main\Loader;

class EventHandler
{
	public static function onGetClientProviderEventHandler(): ClientProviderInterface|null
	{
		if (!Loader::includeModule('booking'))
		{
			return null;
		}

		return new ClientProvider();
	}
}
