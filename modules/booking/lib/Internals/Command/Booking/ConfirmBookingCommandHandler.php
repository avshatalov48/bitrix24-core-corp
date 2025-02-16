<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Exception\Booking\ConfirmBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Journal\JournalEvent;
use Bitrix\Booking\Internals\Journal\JournalType;

class ConfirmBookingCommandHandler
{
	public function __invoke(ConfirmBookingCommand $command): Booking
	{
		$booking = (new BookingConfirmLink())->getBookingByHash($command->hash);

		return Container::getTransactionHandler()->handle(
			fn: function() use ($command, $booking) {

				$booking->setConfirmed(true);
				$booking = Container::getBookingRepository()->save($booking);

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
