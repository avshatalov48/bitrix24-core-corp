<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Internals\Query\Sort;
use Bitrix\Booking\Internals\Query\SortInterface;

class ResourceSort extends Sort implements SortInterface
{
	protected function getAllowedFields(): array
	{
		return [
			'ID',
			'TYPE.NAME',
			'NAME',
			'DESCRIPTION',
		];
	}
}
