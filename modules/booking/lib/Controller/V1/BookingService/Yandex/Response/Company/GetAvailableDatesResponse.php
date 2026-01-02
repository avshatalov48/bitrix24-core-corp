<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\BookingService\Yandex\Response\Company;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection\AvailableDateCollection;

class GetAvailableDatesResponse implements \JsonSerializable
{
	public function __construct(
		public readonly AvailableDateCollection $availableDateCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'availableDates' => $this->availableDateCollection->toArray(),
		];
	}
}
