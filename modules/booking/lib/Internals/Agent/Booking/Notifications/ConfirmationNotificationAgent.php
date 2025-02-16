<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Agent\Booking\Notifications;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Feature\BookingConfirmReminder;
use Bitrix\Booking\Internals\Notifications\BookingMessageCreatorFactory;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Internals\Query\Booking\GetListSort;
use Bitrix\Booking\Internals\Query\Booking\GetListSelect;
use Bitrix\Booking\Internals\Time;

class ConfirmationNotificationAgent
{
	public static function execute(): string
	{
		$bookingCollection = Container::getBookingRepository()->getList(
			limit: 50,
			filter: new GetListFilter([
				'IS_PRIMARY_RESOURCE_CONFIRMATION_ON' => true,
				'WITHIN_CURRENT_DAYTIME' => true,
				'STARTS_IN_LESS_THAN' => Time::SECONDS_IN_DAY,
				'IS_CONFIRMED' => false,
				'HAS_CLIENTS' => true,
				'HAS_RESOURCES' => true,
				'MESSAGE_OF_TYPE_SENT' => [
					[
						'EXISTS' => true,
						'TYPE' => NotificationType::Info->value,
					],
					[
						'EXISTS' => false,
						'TYPE' => NotificationType::Confirmation->value,
						'MINUTES' => Time::MINUTES_IN_DAY,
					],
				],
				'MESSAGE_OF_TYPE_TRIED' => [
					[
						'EXISTS' => false,
						'TYPE' => NotificationType::Confirmation->value,
						'MINUTES' => Time::MINUTES_IN_HOUR,
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
				->createMessageOfType(NotificationType::Confirmation)
				?->send($booking);

			$remindManagerAfter = (new BookingConfirmReminder())->getRemindManagerAfter();
			$agentName = '\\Bitrix\\Booking\\Internals\\Agent\\Booking\\Notifications\\NotifyManagerAboutNonConfirmedBookingAgent::notify(' . $booking->getId() . ');';

			\CAgent::AddAgent(
				name: $agentName,
				module: 'booking',
				next_exec: \ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $remindManagerAfter, 'FULL'),
				existError: false,
			);
		}

		return '\\' . static::class . '::execute();';
	}
}
