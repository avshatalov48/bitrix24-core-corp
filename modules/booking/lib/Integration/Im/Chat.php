<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Im;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Chat
{
	public function sendSystemNotification(
		int $toUserId,
		string $notifyTag,
		string $notifyEvent,
		callable $titleFn,
		callable $messageFn,
		int $fromUserId = 1,
	): Result
	{
		if (!Loader::includeModule('im'))
		{
			return new Result();
		}

		$params = [
			'NOTIFY_MODULE' => 'booking',
			'TITLE' => $titleFn,
			'FROM_USER_ID' => $fromUserId,
			'TO_USER_ID' => $toUserId,
			'NOTIFY_EVENT' => $notifyEvent,
			'NOTIFY_TAG' => $notifyTag,
			'MESSAGE' => $messageFn,
			'MESSAGE_OUT' => $messageFn,
		];

		return \CIMNotify::Add($params) !== false
			? new Result()
			: (new Result())->addError(new Error('sendSystemNotification failed'))
		;
	}

	public function onBookingCanceled(Booking $booking): Result
	{
		$title = fn (?string $languageId = null) => '';

		$bookingDateFromTs = $booking->getDatePeriod()?->getDateFrom()->getTimestamp() ?? time();
		$bookingDate = FormatDate('l, j F Y H:i', $bookingDateFromTs);
		$clientName = $booking->getPrimaryClient()?->getData()['name'] ?? null;

		$message = fn (?string $languageId = null) => Loc::getMessage(
			'BOOKING_IM_SYSTEM_NOTIFICATION_ON_BOOKING_CANCELED',
			[
				'#BOOKING_DATE#' => $bookingDate,
				'#CLIENT_NAME#' => $clientName,
				'#BOOKING_URL#' => '/booking/detail/' . $booking->getId() . '/',
			],
			$languageId
		);

		return $this->sendSystemNotification(
			toUserId: $booking->getCreatedBy(),
			notifyTag: 'BOOKING|CANCELED|' . $booking->getId(),
			notifyEvent: 'info',
			titleFn: $title,
			messageFn: $message,
		);
	}
}
