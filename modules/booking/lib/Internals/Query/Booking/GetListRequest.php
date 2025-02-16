<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Booking;

class GetListRequest
{
	public function __construct(
		public readonly int $userId,
		public readonly int|null $limit = null,
		public readonly int|null $offset = null,
		public readonly GetListFilter|null $filter = null,
		public readonly GetListSort|null $sort = null,
		public readonly GetListSelect|null $select = null,
		public readonly bool $withCounters = false,
		public readonly bool $withClientData = false,
		public readonly bool $withExternalData = false,
	)
	{
	}
}
