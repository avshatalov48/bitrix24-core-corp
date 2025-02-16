<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

class GetTotalRequest
{
	public function __construct(
		public readonly ResourceFilter|null $filter = null,
	)
	{
	}
}
