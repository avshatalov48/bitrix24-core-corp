<?php

namespace Bitrix\Booking\Internals\Service\Feature;

use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Main\Config\Option;

class BookingConfirmReminder
{
	public function bookingAutoConfirmedPeriod(): \DateInterval
	{
		// booking is auto confirmed if startFrom < auto_confirm_period (default is 1 day)
		$minutes = Option::get('booking', 'auto_confirm_period', 1440); // minutes

		return new \DateInterval('PT' . $minutes . 'M');
	}

	public function getRemindManagerAfter(): int
	{
		// remind manager about unconfirmed booking after 2 hours
		// since confirmation link sent to the client

		return Time::SECONDS_IN_HOUR * 2;
	}
}
