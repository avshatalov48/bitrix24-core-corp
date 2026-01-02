<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity\Message\BookingMessage;
use Bitrix\Booking\Entity\Message\BookingMessageCollection;

interface BookingMessageRepositoryInterface
{
	public function getByExternalId(string $senderModule, string $senderCode, int $externalId): BookingMessage|null;
	public function getByBookingIds(array $bookingIds): BookingMessageCollection;
}
