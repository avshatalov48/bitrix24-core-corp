<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications\Agent;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Booking\BookingSort;

class DelayedNotificationAgent
{
	public static function execute(): string
	{
		$repository = Container::getBookingRepository();

		$filter = new BookingFilter([
			'IS_PRIMARY_RESOURCE_DELAYED_ON' => true,
			'IS_DELAYED' => true,
			'HAS_CLIENTS' => true,
			'HAS_RESOURCES' => true,
			'MESSAGE_OF_TYPE_SENT' => [
				[
					'EXISTS' => true,
					'TYPE' => NotificationType::Info->value,
				],
				[
					'EXISTS' => false,
					'TYPE' => NotificationType::Delayed->value,
					'MINUTES' => Time::MINUTES_IN_DAY,
				],
			],
			'MESSAGE_OF_TYPE_TRIED' => [
				[
					'EXISTS' => false,
					'TYPE' => NotificationType::Delayed->value,
					'MINUTES' => Time::MINUTES_IN_HOUR,
					'COUNT' => 3,
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
			Container::getMessageSender()->send($booking, NotificationType::Delayed);

			Container::getJournalService()
				->append(
					new JournalEvent(
						entityId: $booking->getId(),
						type: JournalType::BookingDelayedNotificationInitialized,
						data: [],
					),
				);
		}

		return '\\' . static::class . '::execute();';
	}
}
