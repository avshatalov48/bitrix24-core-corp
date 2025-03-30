<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Booking;

use Bitrix\Booking\Provider\Params\Sort;

class BookingSort extends Sort
{
	protected function getAllowedFields(): array
	{
		return [
			'ID',
			'DATE_FROM',
			'DATE_TO',
			'IS_RECURRING',
		];
	}
}
