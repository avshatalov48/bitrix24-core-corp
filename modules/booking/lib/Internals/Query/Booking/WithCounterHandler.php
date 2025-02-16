<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\CounterDictionary;

class WithCounterHandler
{
	public function __invoke(WithCounterRequest $request)
	{
		$counterRepository = Container::getCounterRepository();
		$bookingCollection = $request->bookingCollection;

		/** @var Booking $booking */
		foreach ($bookingCollection as $booking)
		{
			$counters = [];

			$value = $counterRepository->get(
				userId: $request->userId,
				type: CounterDictionary::BookingUnConfirmed,
				entityId: $booking->getId(),
			);
			$counters[] = [
				'type' => CounterDictionary::BookingUnConfirmed->value,
				'value' => $value,
			];

			$value += $counterRepository->get(
				userId: $request->userId,
				type: CounterDictionary::BookingDelayed,
				entityId: $booking->getId(),
			);
			$counters[] = [
				'type' => CounterDictionary::BookingDelayed->value,
				'value' => $value,
			];

			$booking->setCounter($value);
			$booking->setCounters($counters);
		}

		return $bookingCollection;
	}
}
