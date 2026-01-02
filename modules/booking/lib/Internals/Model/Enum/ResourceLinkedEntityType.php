<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\Enum;

enum ResourceLinkedEntityType: string
{
	case Sku = 'sku';
	case Calendar = 'calendar';
}
