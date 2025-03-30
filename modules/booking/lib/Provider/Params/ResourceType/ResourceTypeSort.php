<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\ResourceType;

use Bitrix\Booking\Provider\Params\Sort;

class ResourceTypeSort extends Sort
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
