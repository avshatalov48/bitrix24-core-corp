<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\Resource\ResourceCollection;

class ResourceDialogResponse implements \JsonSerializable
{
	public function __construct(
		public readonly BookingCollection $bookingCollection,
		public readonly ResourceCollection $resourceCollection,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'bookings' => $this->bookingCollection->toArray(),
			'resources' => $this->resourceCollection->toArray(),
		];
	}
}
