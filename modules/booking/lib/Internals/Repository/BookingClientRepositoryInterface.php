<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Booking\Booking;

interface BookingClientRepositoryInterface
{
	public function getTotalClients(): int;
	public function getTotalNewClientsToday(array $bookingIds): int;

	public function link(Booking $booking, Entity\Booking\ClientCollection $clientCollection): void;

	public function unLink(Booking $booking, Entity\Booking\ClientCollection $clientCollection): void;

	public function unLinkByFilter(array $filter): void;
}
