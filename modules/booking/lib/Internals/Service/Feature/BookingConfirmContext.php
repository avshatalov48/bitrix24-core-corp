<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Feature;

use Bitrix\Booking\Internals\Service\DictionaryTrait;

enum BookingConfirmContext: string
{
	use DictionaryTrait;

	case Cancel = 'c';
	case Delayed = 'd';
}
