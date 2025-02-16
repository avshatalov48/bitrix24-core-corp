<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Notifications;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Integration\Booking\Message\MessageTemplateBased;

class BookingMessageCreatorFactory
{
	public static function create(Booking $booking): BookingMessageCreator
	{
		$messageSender = MessageSenderPicker::pickByBooking($booking);
		if ($messageSender->getMessageClass() === MessageTemplateBased::class)
		{
			return new BookingMessageTemplateBasedCreator($messageSender);
		}

		return new BookingMessageBodyBasedCreator($messageSender);
	}
}
