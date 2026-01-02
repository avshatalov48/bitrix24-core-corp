<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog\ExternalData;

use Bitrix\Main\Type\Contract\Arrayable;

class PermissionDto implements Arrayable
{
	public function __construct(
		public readonly bool $read = false,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'read' => $this->read,
		];
	}
}
