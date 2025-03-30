<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Internals\Service\DictionaryTrait;

enum BookingVisitStatus: string
{
	use DictionaryTrait;

	case Unknown = 'unknown';
	case Visited = 'visited';
	case NotVisited = 'notVisited';
}
