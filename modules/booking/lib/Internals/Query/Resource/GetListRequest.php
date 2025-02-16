<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

class GetListRequest
{
	public function __construct(
		public readonly int $userId,
		public readonly int|null $limit = null,
		public readonly int|null $offset = null,
		public readonly ResourceFilter|null $filter = null,
		public readonly ResourceSort|null $sort = null,
	)
	{
	}
}
