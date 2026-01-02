<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Main\Loader;

class BookingProvider
{
	public function isBookingDelayed(int $bookingId): bool|null
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return $this->getById($bookingId)?->isDelayed();
	}

	private function getById(int $id): ?Booking
	{
		return (new \Bitrix\Booking\Provider\BookingProvider())->getById(userId: 0, id: $id);
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('booking');
	}
}
