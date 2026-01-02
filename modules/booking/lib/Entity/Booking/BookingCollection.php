<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\EventInterface;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;

/**
 * @method \Bitrix\Booking\Entity\Booking\Booking|null getFirstCollectionItem()
 * @method \ArrayIterator<Booking> getIterator()
 */
class BookingCollection extends BaseEntityCollection
{
	public function __construct(Booking ...$bookings)
	{
		foreach ($bookings as $booking)
		{
			$this->collectionItems[] = $booking;
		}
	}

	public function getClientCollection(): ClientCollection
	{
		$result = new ClientCollection();

		foreach ($this as $booking)
		{
			foreach ($booking->getClientCollection() as $client)
			{
				$result->add($client);
			}
		}

		return $result;
	}

	public function getExternalDataCollection(): ExternalDataCollection
	{
		$result = new ExternalDataCollection();

		foreach ($this as $booking)
		{
			foreach ($booking->getExternalDataCollection() as $item)
			{
				$result->add($item);
			}
		}

		return $result;
	}

	public function filterByDatePeriod(DatePeriod $datePeriod): self
	{
		return new self(
			...array_filter(
				$this->collectionItems,
				static function(EventInterface $event) use ($datePeriod)
				{
					return $event->doEventsIntersect($datePeriod);
				}
			)
		);
	}

	public function diff(BookingCollection $collectionToCompare): BookingCollection
	{
		return new BookingCollection(...$this->baseDiff($collectionToCompare));
	}

	public function containsId(int $id): bool
	{
		return in_array($id, $this->getEntityIds(), true);
	}

	public function addUnique(EntityInterface $entity): bool
	{
		if ($this->containsId($entity->getId()))
		{
			return false;
		}

		$this->add($entity);

		return true;
	}

	public function filter(?callable $filter): self
	{
		return new self(
			...array_filter(
				$this->collectionItems,
				$filter
			)
		);
	}
}
