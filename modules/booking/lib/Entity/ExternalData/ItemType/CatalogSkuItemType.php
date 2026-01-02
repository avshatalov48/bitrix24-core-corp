<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData\ItemType;

class CatalogSkuItemType extends BaseItemType
{
	public function getModuleId(): string
	{
		return 'catalog';
	}

	public function getEntityTypeId(): string
	{
		return 'SKU';
	}
}
