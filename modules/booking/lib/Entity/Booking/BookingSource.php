<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Internals\Service\DictionaryTrait;

enum BookingSource: string
{
	use DictionaryTrait;

	case Internal = 'internal';
	case Yandex = 'yandex';
}
