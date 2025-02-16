<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access;

enum ResourceAction: string
{
	case Create = 'resource_create';
	case Read = 'resource_read';
	case Update = 'resource_update';
	case Delete = 'resource_delete';
}
