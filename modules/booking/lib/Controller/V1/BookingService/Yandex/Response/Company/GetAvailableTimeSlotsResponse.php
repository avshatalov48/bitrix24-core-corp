<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\AvailableTimeSlotCollection;

class GetAvailableTimeSlotsResponse implements \JsonSerializable
{
	public function __construct(
		public readonly AvailableTimeSlotCollection $availableTimeSlotCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'availableTimeSlots' => $this->availableTimeSlotCollection->toArray(),
		];
	}
}
