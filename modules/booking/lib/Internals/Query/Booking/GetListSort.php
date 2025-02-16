<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

use Bitrix\Booking\Internals\Query\Sort;
use Bitrix\Booking\Internals\Query\SortInterface;

class GetListSort extends Sort implements SortInterface
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
