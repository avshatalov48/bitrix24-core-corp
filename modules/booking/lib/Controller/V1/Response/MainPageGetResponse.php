<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response;

use Bitrix\Booking\Entity\Booking\BookingCollection;
use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Entity\WaitListItem\WaitListItemCollection;

class MainPageGetResponse implements \JsonSerializable
{
	public function __construct(
		public readonly Favorites|null $favorites,
		public readonly BookingCollection $bookingCollection,
		public readonly ResourceTypeCollection $resourceTypeCollection,
		public readonly string|null $providerModuleId,
		public readonly array $clientsDataRecent,
		public readonly bool $isCurrentSenderAvailable,
		public readonly WaitListItemCollection $waitListItemCollection,
		public readonly bool $isIntersectionForAll,
		public readonly array $counters,
		public readonly array $formsMenu,
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
			'waitListItems' => $this->waitListItemCollection->toArray(),
			'isIntersectionForAll' => $this->isIntersectionForAll,
			'isCurrentSenderAvailable' => $this->isCurrentSenderAvailable,
			'formsMenu' => $this->formsMenu,
		];
	}
}
