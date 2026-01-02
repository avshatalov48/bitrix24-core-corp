<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1\Response\CalendarData;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Main\Type\Contract\Arrayable;

class CalendarDataBookingInfoResponse implements \JsonSerializable, Arrayable
{
	/**
	 * @param ResourceDto[] $resources
	 * @param ServiceDto[] $services
	 */
	public function __construct(
		public readonly int $id,
		public readonly array $resources,
		public readonly array $services = [],
		public readonly ClientDto|null $client = null,
		public readonly string|null $note = null,
	)
	{

	}

	public static function fromEntity(Booking $booking): self
	{
		$client = $booking->getClientCollection()->getPrimaryClient();

		return new self(
			id: $booking->getId(),
			resources: array_map(
				static fn(Resource $resource) => ResourceDto::fromEntity($resource),
				$booking->getResourceCollection()->getCollectionItems()
			),
			services: array_map(
				static fn(ExternalDataItem $externalDataItem) => ServiceDto::fromEntity($externalDataItem),
				$booking->getExternalDataCollection()
					->filterByType((new CatalogSkuItemType())->buildFilter())
					->getCollectionItems(),
			),
			client: $client ? ClientDto::fromEntity($client) : null,
			note: $booking->getNote(),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'resources' => array_map(
				static fn(ResourceDto $resource) => $resource->toArray(),
				$this->resources,
			),
			'services' => array_map(
				static fn(ServiceDto $service) => $service->toArray(),
				$this->services,
			),
			'client' => $this->client?->toArray(),
			'note' => $this->note,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
