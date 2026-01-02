<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Booking;

use Bitrix\Booking\Command\Booking\Trait\BookingChangesTrait;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Internals\Exception\Booking\RemoveBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalServiceInterface;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Service\Overbooking\IntersectionResult;
use Bitrix\Booking\Internals\Service\Overbooking\OverbookingService;
use Bitrix\Booking\Provider\BookingProvider;

class RemoveBookingCommandHandler
{
	use BookingChangesTrait;

	private BookingRepositoryInterface $bookingRepository;
	private BookingProvider $bookingProvider;
	private JournalServiceInterface $journalService;
	private OverbookingService $overbookingService;

	public function __construct()
	{
		$this->bookingRepository = Container::getBookingRepository();
		$this->bookingProvider = new BookingProvider();
		$this->journalService = Container::getJournalService();
		$this->overbookingService = Container::getOverbookingService();
	}

	public function __invoke(RemoveBookingCommand $command): void
	{
		$existBooking = $this->bookingProvider->getById(
			userId: $command->removedBy,
			id: $command->id,
		);
		if (!$existBooking)
		{
			throw new RemoveBookingException('booking not found');
		}

		Container::getTransactionHandler()->handle(
			fn: function () use ($command, $existBooking) {
				$this->bookingRepository->remove($command->id);

				$this->journalService->append(
					new JournalEvent(
						entityId: $command->id,
						type: JournalType::BookingDeleted,
						data: $command->toArray(),
					),
				);

				$this->processBookingChanges(
					$existBooking,
					new Booking(),
					new IntersectionResult(new BookingCollection()),
					$command->removedBy,
				);
			},
			errType: RemoveBookingException::class,
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
