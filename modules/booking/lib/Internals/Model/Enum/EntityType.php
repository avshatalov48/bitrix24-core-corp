<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\Enum;

enum EntityType: string
{
	case Booking = 'booking';
	case WaitList = 'wait-list';
}
