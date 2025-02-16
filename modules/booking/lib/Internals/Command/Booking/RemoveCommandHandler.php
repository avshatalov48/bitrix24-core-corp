<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Exception\Booking\RemoveBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class RemoveCommandHandler
{
	public function __invoke(RemoveCommand $command): void
	{
		Container::getTransactionHandler()->handle(
			fn: function() use ($command) {
				Container::getBookingRepository()->remove($command->id);

				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $command->id,
						type: JournalType::BookingDeleted,
						data: $command->toArray(),
					),
				);
			},
			errType: RemoveBookingException::class,
		);
	}
}
