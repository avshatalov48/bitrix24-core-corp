<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Agent\Booking\Notifications;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Notifications\BookingMessageCreatorFactory;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Internals\Query\Booking\GetListSelect;
use Bitrix\Booking\Internals\Query\Booking\GetListSort;
use Bitrix\Booking\Internals\Time;

class ReminderNotificationAgent
{
	public static function execute(): string
	{
		$bookingCollection = Container::getBookingRepository()->getList(
			limit: 50,
			filter: new GetListFilter([
				'IS_PRIMARY_RESOURCE_REMINDER_ON' => true,
				'WITHIN_CURRENT_DAYTIME' => true,
				'STARTS_IN_LESS_THAN' => Time::SECONDS_IN_DAY,
				'IS_SAME_DAY_OR_EARLY_MORNING_START' => true,
				'HAS_CLIENTS' => true,
				'HAS_RESOURCES' => true,
				'MESSAGE_OF_TYPE_SENT' => [
					[
						'EXISTS' => true,
						'TYPE' => NotificationType::Info->value,
					],
					[
						'EXISTS' => false,
						'TYPE' => NotificationType::Reminder->value,
						'MINUTES' => Time::MINUTES_IN_DAY,
					],
				],
				'MESSAGE_OF_TYPE_TRIED' => [
					[
						'EXISTS' => false,
						'TYPE' => NotificationType::Reminder->value,
						'MINUTES' => Time::MINUTES_IN_HOUR,
						'COUNT' => 3,
					],
				],
			]),
			sort: new GetListSort([
				'ID' => 'ASC',
			]),
			select: new GetListSelect([
				'EXTERNAL_DATA',
				'CLIENTS',
				'RESOURCES',
			]),
		);

		foreach ($bookingCollection as $booking)
		{
			$messageCreator = BookingMessageCreatorFactory::create($booking)
				->setBooking($booking);

			$messageCreator
				->createMessageOfType(NotificationType::Reminder)
				?->send($booking);
		}

		return '\\' . static::class . '::execute();';
	}
}
