<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Ui\EntitySelector;

enum EntityId: string
{
	case Resource = 'resource';
	case ResourceType = 'resource-type';
}
