<?php

declare(strict_types=1);

namespace Bitrix\Booking\Service;

use Bitrix\Booking\Internals\Command;
use Bitrix\Booking\Entity;

class BookingService
{
	public function create(int $userId, Entity\Booking\Booking $booking): Entity\Booking\Booking
	{
		$command = new Command\Booking\AddCommand(
			createdBy: $userId,
			booking: $booking,
		);

		return (new Command\Booking\AddCommandHandler())($command);
	}

	public function update(int $userId, Entity\Booking\Booking $booking): Entity\Booking\Booking
	{
		$command = new Command\Booking\UpdateCommand(
			updatedBy: $userId,
			booking: $booking,
		);

		return (new Command\Booking\UpdateCommandHandler())($command);
	}

	public function delete(int $userId, int $id): void
	{
		$command = new Command\Booking\RemoveCommand(
			id: $id,
			removedBy: $userId,
		);

		(new Command\Booking\RemoveCommandHandler())($command);
	}
}
