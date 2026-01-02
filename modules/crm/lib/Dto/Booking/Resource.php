<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

use Bitrix\Main\Type\Contract\Arrayable;

class Resource implements Arrayable
{
	public function __construct(
		public readonly string $typeName,
		public readonly string $name,
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			typeName: $params['typeName'],
			name: $params['name'],
		);
	}

	public function toArray(): array
	{
		return [
			'typeName' => $this->typeName,
			'name' => $this->name,
		];
	}
}
