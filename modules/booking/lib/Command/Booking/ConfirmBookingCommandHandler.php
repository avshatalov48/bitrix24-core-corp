<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Exception\Booking\ConfirmBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalType;

class ConfirmBookingCommandHandler
{
	public function __invoke(ConfirmBookingCommand $command): Booking
	{
		$booking = (new BookingConfirmLink())->getBookingByHash($command->hash);

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command, $booking) {

				// update booking with confirmed flag
				$booking->setConfirmed(true);
				Container::getBookingRepository()->save($booking);

				// fire new BookingConfirmed event
				Container::getJournalService()->append(
					new JournalEvent(
						entityId: $booking->getId(),
						type: JournalType::BookingConfirmed,
						data: array_merge(
							$command->toArray(),
							[
								'booking' => $booking->toArray(),
							],
						),
					),
				);

				return $booking;
			},
			errType: ConfirmBookingException::class,
		);
	}
}
