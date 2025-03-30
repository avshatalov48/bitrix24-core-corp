<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications\Agent;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmReminder;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Booking\BookingSort;

class ConfirmationNotificationAgent
{
	public static function execute(): string
	{
		$repository = Container::getBookingRepository();

		$filter = new BookingFilter([
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
					'TYPE' => NotificationType::Info->value,
					'MINUTES' => Time::MINUTES_IN_DAY,
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
		]);

		$bookingCollection = $repository->getList(
			limit: 50,
			filter: $filter,
			sort: (new BookingSort([
				'ID' => 'ASC',
			]))->prepareSort(),
			select: (new BookingSelect([
				'EXTERNAL_DATA',
				'CLIENTS',
				'RESOURCES',
			]))->prepareSelect(),
		);

		foreach ($bookingCollection as $booking)
		{
            Container::getMessageSender()->send($booking, NotificationType::Confirmation);

			$remindManagerAfter = (new BookingConfirmReminder())->getRemindManagerAfter();
			$agentName = '\\Bitrix\\Booking\\Internals\\Service\\Notifications\\Agent\\NotifyManagerAboutNonConfirmedBookingAgent::notify(' . $booking->getId() . ');';

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
