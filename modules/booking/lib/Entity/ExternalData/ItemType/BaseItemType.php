<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData\ItemType;

use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;

abstract class BaseItemType
{
	abstract public function getModuleId(): string;

	abstract public function getEntityTypeId(): string;

	public function buildFilter(): ItemTypeFilter
	{
		return new ItemTypeFilter(
			moduleId: $this->getModuleId(),
			entityTypeId: $this->getEntityTypeId(),
		);
	}

	public function createItem(): ExternalDataItem
	{
		return (new ExternalDataItem())
			->setModuleId($this->getModuleId())
			->setEntityTypeId($this->getEntityTypeId())
		;
	}
}
