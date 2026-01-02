<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Model\BookingExternalDataTable;
use Bitrix\Booking\Internals\Model\Enum\EntityType;

class BookingExternalDataRepository
{
	public function link(
		int $entityId,
		EntityType $entityType,
		Entity\ExternalData\ExternalDataCollection $collection
	): void
	{
		$data = [];

		/** @var Entity\ExternalData\ExternalDataItem $item */
		foreach ($collection as $item)
		{
			$data[] = [
				'ENTITY_ID' => $entityId,
				'MODULE_ID' => $item->getModuleId(),
				'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'VALUE' => $item->getValue(),
				'ENTITY_TYPE' => $entityType->value,
			];
		}

		if (!empty($data))
		{
			BookingExternalDataTable::addMulti($data, true);
		}
	}

	public function unLink(
		int $entityId,
		EntityType $entityType,
		Entity\ExternalData\ExternalDataCollection $collection
	): void
	{
		/** @var Entity\ExternalData\ExternalDataItem $item */
		foreach ($collection as $item)
		{
			$this->unLinkByFilter([
				'=ENTITY_ID' => $entityId,
				'=MODULE_ID' => $item->getModuleId(),
				'=ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'=VALUE' => $item->getValue(),
				'=ENTITY_TYPE' => $entityType->value,
			]);
		}
	}

	public function unLinkByFilter(array $filter): void
	{
		BookingExternalDataTable::deleteByFilter($filter);
	}
}
