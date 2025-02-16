<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Model\BookingResourceTable;

class BookingResourceRepository
{
	public function link(Booking $booking, ResourceCollection $resourceCollection): void
	{
		$data = [];

		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			$data[] = [
				'BOOKING_ID' => $booking->getId(),
				'RESOURCE_ID' => $resource->getId(),
			];
		}

		if (!empty($data))
		{
			BookingResourceTable::addMulti($data, true);
		}
	}

	public function unLink(Booking $booking, ResourceCollection $resourceCollection): void
	{
		/** @var Resource $resource */
		foreach ($resourceCollection as $resource)
		{
			BookingResourceTable::deleteByFilter([
				'=BOOKING_ID' => $booking->getId(),
				'=RESOURCE_ID' => $resource->getId(),
			]);
		}
	}
}
