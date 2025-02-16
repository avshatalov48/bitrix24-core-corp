<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Entity\Resource\Resource;

class GetByIdResponse
{
	public function __construct(
		public readonly Resource|null $resource,
	)
	{
	}
}
