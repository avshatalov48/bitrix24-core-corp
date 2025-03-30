<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Internals\Exception\Booking\RemoveBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class RemoveBookingCommandHandler
{
	public function __invoke(RemoveBookingCommand $command): void
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
