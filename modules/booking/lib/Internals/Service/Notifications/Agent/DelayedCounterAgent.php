<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Notifications\Agent;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlHelper;

class DelayedCounterAgent
{
	private static Connection $connection;
	private static SqlHelper $sqlHelper;

	public static function execute(): string
	{
		self::$connection = Application::getConnection();
		self::$sqlHelper = self::$connection->getSqlHelper();

		$bookingIdRows = self::$connection->query(self::getSql())->fetchAll();

		(new BookingHandlerService())->handleBookings(
			array_column($bookingIdRows, 'ID'),
			static function (Booking $booking) {
				Container::getJournalService()
					->append(
						new JournalEvent(
							entityId: $booking->getId(),
							type: JournalType::BookingDelayedCounterActivated,
							data: [
								'booking' => $booking->toArray(),
							],
						),
					)
				;
			}
		);

		return '\\' . self::class . '::execute();';
	}

	private static function getSql(): string
	{
		$currentTimestamp = time();
		$oneDayBehindTimestamp = $currentTimestamp - Time::SECONDS_IN_DAY;

		return "
			SELECT b.ID
			FROM b_booking_booking b
			JOIN b_booking_booking_resource bbr ON bbr.BOOKING_ID = b.ID AND bbr.IS_PRIMARY = 'Y'
			JOIN b_booking_resource_notification_settings rns ON rns.RESOURCE_ID = bbr.RESOURCE_ID
			LEFT JOIN b_booking_scorer bs ON 
				bs.ENTITY_ID = b.ID 
				AND bs.TYPE = '" . self::$sqlHelper->forSql(CounterDictionary::BookingDelayed->value) . "'
				AND bs.USER_ID = b.CREATED_BY
			WHERE
				b.IS_DELETED = 'N'
			  	AND rns.IS_DELAYED_ON = 'Y'
			  	AND b.DATE_FROM <= $currentTimestamp
			  	AND b.DATE_FROM > $oneDayBehindTimestamp
			  	AND bs.VALUE IS NULL
			  	AND b.DATE_TO > $currentTimestamp
			  	AND b.VISIT_STATUS = '" . self::$sqlHelper->forSql(BookingVisitStatus::Unknown->value) . "'
				AND b.DATE_FROM + rns.DELAYED_COUNTER_DELAY <= $currentTimestamp
		";
	}
}
