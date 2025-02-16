<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;

class MainPageGetResponse implements \JsonSerializable
{
	public function __construct(
		public readonly Favorites|null $favorites,
		public readonly BookingCollection $bookingCollection,
		public readonly ResourceTypeCollection $resourceTypeCollection,
		public readonly string|null $providerModuleId,
		public readonly array $clientsDataRecent,
		public readonly bool $isCurrentSenderAvailable,
		public readonly bool $isIntersectionForAll = true,
		public readonly array $counters = [],
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'favorites' => $this->favorites?->toArray(),
			'bookings' => $this->bookingCollection->toArray(),
			'resourceTypes' => $this->resourceTypeCollection->toArray(),
			'clients' => [
				'providerModuleId' => $this->providerModuleId,
				'recent' => $this->clientsDataRecent,
			],
			'counters' => $this->counters,
			'isIntersectionForAll' => $this->isIntersectionForAll,
			'isCurrentSenderAvailable' => $this->isCurrentSenderAvailable,
		];
	}
}
