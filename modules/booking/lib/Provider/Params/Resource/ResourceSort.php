<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Resource;

use Bitrix\Booking\Provider\Params\Sort;

class ResourceSort extends Sort
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
