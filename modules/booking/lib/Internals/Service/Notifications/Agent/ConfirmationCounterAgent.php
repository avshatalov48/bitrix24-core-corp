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

class ConfirmationCounterAgent
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
							type: JournalType::BookingConfirmCounterActivated,
							data: [
								'booking' => $booking->toArray(),
							],
						),
					);
			}
		);

		return '\\' . self::class . '::execute();';
	}

	private static function getSql(): string
	{
		$currentTimestamp = time();
		$oneDayAheadTimestamp = $currentTimestamp + Time::SECONDS_IN_DAY;

		return "
			SELECT b.ID
			FROM b_booking_booking b
			JOIN b_booking_booking_resource bbr ON bbr.BOOKING_ID = b.ID AND bbr.IS_PRIMARY = 'Y'
			JOIN b_booking_resource_notification_settings rns ON rns.RESOURCE_ID = bbr.RESOURCE_ID
			LEFT JOIN b_booking_scorer bs ON 
				bs.ENTITY_ID = b.ID 
				AND bs.TYPE = '" . self::$sqlHelper->forSql(CounterDictionary::BookingUnConfirmed->value) . "'
				AND bs.USER_ID = b.CREATED_BY
			WHERE
				b.IS_DELETED = 'N'
				AND rns.IS_CONFIRMATION_ON = 'Y'
			  	AND b.DATE_FROM > $currentTimestamp
			  	AND b.DATE_FROM < $oneDayAheadTimestamp
			  	AND b.IS_CONFIRMED = 'N'
				AND bs.VALUE IS NULL
				AND b.VISIT_STATUS = '" . self::$sqlHelper->forSql(BookingVisitStatus::Unknown->value) . "'
				AND b.DATE_FROM - rns.CONFIRMATION_COUNTER_DELAY <= $currentTimestamp
		";
	}
}
