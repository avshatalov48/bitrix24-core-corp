<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Exception\Booking\ConfirmBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class CancelBookingCommandHandler
{
	public function __invoke(CancelBookingCommand $command): void
	{
		$booking = (new BookingConfirmLink())->getBookingByHash($command->hash);

		Container::getTransactionHandler()->handle(
			fn: function() use ($command, $booking) {

				Container::getBookingRepository()->remove($booking->getId());

				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $booking->getId(),
						type: JournalType::BookingCanceled,
						data: array_merge(
							$command->toArray(),
							[
								'id' => $booking->getId(),
								'booking' => $booking->toArray(),
							],
						),
					),
				);
			},
			errType: ConfirmBookingException::class,
		);
	}
}
