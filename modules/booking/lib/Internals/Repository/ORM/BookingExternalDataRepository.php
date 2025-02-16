<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Model\BookingExternalDataTable;

class BookingExternalDataRepository
{
	public function link(Booking $booking, Entity\Booking\ExternalDataCollection $collection): void
	{
		$data = [];

		/** @var Entity\Booking\ExternalDataItem $item */
		foreach ($collection as $item)
		{
			$data[] = [
				'BOOKING_ID' => $booking->getId(),
				'MODULE_ID' => $item->getModuleId(),
				'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'VALUE' => $item->getValue(),
			];
		}

		if (!empty($data))
		{
			BookingExternalDataTable::addMulti($data, true);
		}
	}

	public function unLink(Booking $booking, Entity\Booking\ExternalDataCollection $collection): void
	{
		/** @var Entity\Booking\ExternalDataItem $item */
		foreach ($collection as $item)
		{
			$this->unLinkByFilter([
				'=BOOKING_ID' => $booking->getId(),
				'=MODULE_ID' => $item->getModuleId(),
				'=ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'=VALUE' => $item->getValue(),
			]);
		}
	}

	public function unLinkByFilter(array $filter): void
	{
		BookingExternalDataTable::deleteByFilter($filter);
	}
}
