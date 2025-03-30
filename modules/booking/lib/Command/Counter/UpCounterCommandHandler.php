<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Counter;

use Bitrix\Booking\Internals\Integration\Pull\PushEvent;
use Bitrix\Booking\Internals\Integration\Pull\PushService;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull\PushPullCommandType;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;

class UpCounterCommandHandler
{
	private CounterRepositoryInterface $counterRepository;
	private BookingRepositoryInterface $bookingRepository;

	public function __construct()
	{
		$this->counterRepository = Container::getCounterRepository();
		$this->bookingRepository = Container::getBookingRepository();
	}

	public function __invoke(UpCounterCommand $command): void
	{
		match ($command->type)
		{
			CounterDictionary::BookingUnConfirmed,
			CounterDictionary::BookingDelayed => $this->handle($command),
			default => '',
		};
	}

	private function handle(UpCounterCommand $command): void
	{
		$booking = $this->bookingRepository->getById($command->entityId);

		if (!$booking)
		{
			return;
		}

		$this->counterRepository->up(
			entityId: $booking->getId(),
			type: $command->type,
			userId: $booking->getCreatedBy()
		);

		\CUserCounter::Set(
			$booking->getCreatedBy(),
			CounterDictionary::LeftMenu->value,
			$this->counterRepository->get($booking->getCreatedBy(), CounterDictionary::Total),
			'**',
		);

		(new PushService())->sendEvent(
			new PushEvent(
				command: PushPullCommandType::CountersUpdated->value,
				tag: PushPullCommandType::CountersUpdated->getTag(),
				params: [],
				entityId: $command->entityId,
			)
		);
	}
}
