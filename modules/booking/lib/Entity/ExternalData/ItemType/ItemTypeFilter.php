<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData\ItemType;

class ItemTypeFilter
{
	public function __construct(
		public readonly string $moduleId,
		public readonly string $entityTypeId
	)
	{
	}
}
