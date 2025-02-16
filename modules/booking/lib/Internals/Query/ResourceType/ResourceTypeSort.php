<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\ResourceType;

use Bitrix\Booking\Internals\Query\Sort;
use Bitrix\Booking\Internals\Query\SortInterface;

class ResourceTypeSort extends Sort implements SortInterface
{
	protected function getAllowedFields(): array
	{
		return [
			'ID',
			'NAME',
			'MODULE_ID',
			'CODE',
		];
	}
}
