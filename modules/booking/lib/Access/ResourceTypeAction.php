<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access;

enum ResourceTypeAction: string
{
	case Create = 'resource_type_create';
	case Read = 'resource_type_read';
	case Update = 'resource_type_update';
	case Delete = 'resource_type_delete';
}
