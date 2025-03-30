<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Interfaces\ProviderInterface;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Crm\Activity;

class EventHandler
{
	public static function onGetProviderEventHandler(): ProviderInterface|null
	{
		if (!Loader::includeModule('booking'))
		{
			return null;
		}

		return new Provider();
	}

	public static function onBookingAdd(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		Activity\Provider\Booking::onBookingAdded($event->getParameter('booking')->toArray());
	}

	public static function onBookingUpdate(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		$updatedBooking = $event->getParameter('booking');

		Activity\Provider\Booking::onBookingUpdated($updatedBooking->toArray());
	}

	public static function onBookingDelete(Event $event): void
	{
		if (!Loader::includeModule('booking'))
		{
			return;
		}

		Activity\Provider\Booking::onBookingDeleted($event->getParameter('bookingId'));
	}
}
