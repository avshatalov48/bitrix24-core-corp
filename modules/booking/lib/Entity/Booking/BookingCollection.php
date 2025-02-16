<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\EventInterface;

/**
 * @method \Bitrix\Booking\Entity\Booking\Booking|null getFirstCollectionItem()
 * @method Booking[] getIterator()
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
}
