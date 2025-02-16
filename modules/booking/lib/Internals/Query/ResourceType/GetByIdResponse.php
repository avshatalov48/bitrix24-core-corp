<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\ResourceType;

use Bitrix\Booking\Entity\ResourceType\ResourceType;

class GetByIdResponse
{
	public function __construct(
		public readonly ResourceType|null $resourceType,
	)
	{
	}
}
