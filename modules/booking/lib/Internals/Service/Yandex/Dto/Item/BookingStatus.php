<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

enum BookingStatus: string
{
	case Created = 'created';
	case Confirmed = 'confirmed';
	case Visited = 'visited';
	case NotVisited = 'not visited';
	case Cancelled = 'cancelled';
}
