<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\Booking\Trait\BookingChangesTrait;
use Bitrix\Booking\Internals\Exception\Booking\ConfirmBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;

class CancelBookingCommandHandler
{
	use BookingChangesTrait;

	private BookingRepositoryInterface $bookingRepository;
	private JournalServiceInterface $journalService;
	private OverbookingService $overbookingService;

	public function __construct()
	{
		$this->bookingRepository = Container::getBookingRepository();
		$this->journalService = Container::getJournalService();
		$this->overbookingService = Container::getOverbookingService();
	}

	public function __invoke(CancelBookingCommand $command): void
	{
		$booking = (new BookingConfirmLink())->getBookingByHash($command->hash);

		Container::getTransactionHandler()->handle(
			fn: function() use ($command, $booking) {

				$this->bookingRepository->remove($booking->getId());

				$events[] = new JournalEvent(
					entityId: $booking->getId(),
					type: JournalType::BookingCanceled,
					data: array_merge(
						$command->toArray(),
						[
							'id' => $booking->getId(),
							'booking' => $booking->toArray(),
						],
					),
				);
				$events[] = new JournalEvent(
					entityId: $booking->getId(),
					type: JournalType::BookingDeleted,
					data: [
						'id' => $booking->getId(),
						// removed by client, set empty system user
						'removedBy' => 0,
					],
				);

				foreach ($events as $event)
				{
					$this->journalService->append($event);
				}
			},
			errType: ConfirmBookingException::class,
		);
	}

	protected function getOverbookingService(): OverbookingService
	{
		return $this->overbookingService;
	}

	protected function getBookingRepository(): BookingRepositoryInterface
	{
		return $this->bookingRepository;
	}

	protected function getJournalService(): JournalServiceInterface
	{
		return $this->journalService;
	}
}
