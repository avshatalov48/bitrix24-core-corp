<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Internals\Service\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Main\Engine\CurrentUser;

class BookingActivity
{
	public static function getDeleteBookingEndpoint(): string
	{
		return 'booking.api_v1.Booking.delete';
	}

	public static function getSendBookingMessageEndpoint(): string
	{
		return 'booking.api_v1.Message.send';
	}

	public static function getMessageMenuItems(int $bookingId): array
	{
		if (!MessageSenderPicker::canUseCurrentSender())
		{
			return [];
		}

		if ($bookingId <= 0)
		{
			return [];
		}

		$booking = (new BookingProvider())->getById(
			userId: (int)CurrentUser::get()->getId(),
			id: $bookingId
		);
		if (!$booking)
		{
			return [];
		}

		if ($booking->getClientCollection()->isEmpty())
		{
			return [];
		}

		$result = [];

		foreach (NotificationType::cases() as $notificationType)
		{
			if ($notificationType === NotificationType::Feedback)
			{
				continue;
			}

			$result[] = [
				'code' => $notificationType->value,
				'name' => NotificationType::getName($notificationType),
				'params' => [
					'notificationType' => $notificationType->value,
				],
			];
		}

		return $result;
	}
}
