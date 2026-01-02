<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Entity\ExternalData\ItemType\CatalogSkuItemType;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;
use Bitrix\Booking\Entity;
use DateTimeInterface;

class Booking extends Item
{
	private string $id;
	private BookingStatus $status;
	private array $serviceIds;
	private string $resourceId;
	private string $datetime;

	public function __construct(
		string $id,
		BookingStatus $status,
		array $serviceIds,
		string $resourceId,
		string $datetime
	)
	{
		$this->id = $id;
		$this->status = $status;
		$this->serviceIds = array_map('strval', $serviceIds);
		$this->resourceId = $resourceId;
		$this->datetime = $datetime;
	}

	protected function __toArray(): array
	{
		return [
			'id' => $this->id,
			'status' => $this->status->value,
			'serviceIds' => $this->serviceIds,
			'resourceId' => $this->resourceId,
			'datetime' => $this->datetime,
		];
	}

	public static function createFromBooking(Entity\Booking\Booking $booking): self
	{
		$primaryResourceId = $booking->getPrimaryResource()?->getId();
        $serviceIds = $booking->getExternalDataCollection()
			->filterByType((new CatalogSkuItemType())->buildFilter())
			->getValues()
		;

		return new self(
			id: (string)$booking->getId(),
			status: self::getBookingStatus($booking),
			serviceIds: array_map('strval', $serviceIds),
			resourceId: isset($primaryResourceId) ? (string)$primaryResourceId : null,
			datetime: $booking->getDatePeriod()->getDateFrom()->format(DateTimeInterface::ATOM),
		);
	}

	private static function getBookingStatus(Entity\Booking\Booking $booking): BookingStatus
	{
		if ($booking->isDeleted())
		{
			return BookingStatus::Cancelled;
		}

		if ($booking->getVisitStatus() === BookingVisitStatus::Visited)
		{
			return BookingStatus::Visited;
		}

		if ($booking->getVisitStatus() === BookingVisitStatus::NotVisited)
		{
			return BookingStatus::NotVisited;
		}

		if ($booking->isConfirmed())
		{
			return BookingStatus::Confirmed;
		}

		return BookingStatus::Created;
	}
}
