<?php

namespace Bitrix\Booking\Internals\Exception\Booking;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateBookingFromWaitListItemException extends Exception
{
	public function __construct($message = '', int $code = 0)
	{
		$message = $message === '' ? 'Failed creating new booking from wait list item' : $message;
		$code = $code === 0 ? self::CODE_BOOKING_FROM_WAIT_LIST_ITEM_CREATE : $code;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}
