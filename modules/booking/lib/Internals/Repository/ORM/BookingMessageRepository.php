<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity\Message\BookingMessage;
use Bitrix\Booking\Entity\Message\BookingMessageCollection;
use Bitrix\Booking\Internals\Model\BookingMessageTable;
use Bitrix\Booking\Internals\Repository\BookingMessageRepositoryInterface;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;

class BookingMessageRepository implements BookingMessageRepositoryInterface
{
	public function getByExternalId(string $senderModule, string $senderCode, int $externalId): BookingMessage|null
	{
		$bookingMessage = BookingMessageTable::query()
			->setSelect([
				'ID',
				'BOOKING_ID',
				'NOTIFICATION_TYPE',
			])
			->setLimit(1)
			->where('SENDER_MODULE_ID', '=', $senderModule)
			->where('SENDER_CODE', '=', $senderCode)
			->where('EXTERNAL_MESSAGE_ID', '=', $externalId)
			->exec()
			->fetch();
		;
		if (!$bookingMessage)
		{
			return null;
		}

		return $this->createEntityFromOrmRow($bookingMessage);
	}

	public function getByBookingIds(array $bookingIds): BookingMessageCollection
	{
		$result = new BookingMessageCollection();

		$bookingMessages = BookingMessageTable::query()
			->setSelect([
				'ID',
				'BOOKING_ID',
				'NOTIFICATION_TYPE',
			])
			->whereIn('BOOKING_ID', $bookingIds)
			->exec()
			->fetchAll();
		;

		foreach ($bookingMessages as $bookingMessage)
		{
			$result->add(
				$this->createEntityFromOrmRow($bookingMessage)
			);
		}

		return $result;
	}

	private function createEntityFromOrmRow(array $row): BookingMessage
	{
		return (new BookingMessage())
			->setId((int)$row['ID'])
			->setBookingId((int)$row['BOOKING_ID'])
			->setNotificationType(NotificationType::tryFrom($row['NOTIFICATION_TYPE']));
	}
}
