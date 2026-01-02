<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Catalog\ExternalData;

use Bitrix\Main\Type\Contract\Arrayable;

class SkuDataDto implements Arrayable
{
	public function __construct(
		public readonly int $id,
		public readonly string|null $name = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
		];
	}
}
