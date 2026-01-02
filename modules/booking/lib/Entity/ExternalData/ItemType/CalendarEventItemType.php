<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\ExternalData\ItemType;

class CalendarEventItemType extends BaseItemType
{
	public function getModuleId(): string
	{
		return 'calendar';
	}

	public function getEntityTypeId(): string
	{
		return 'EVENT';
	}
}
