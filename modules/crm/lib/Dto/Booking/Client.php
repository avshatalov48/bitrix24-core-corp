<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

use Bitrix\Main\Type\Contract\Arrayable;

class Client implements Arrayable
{
	public function __construct(
		public readonly string $typeModule,
		public readonly string $typeCode,
		public readonly int $id,
		public readonly array $phones = [],
	)
	{
	}

	public static function mapFromArray(array $params): self
	{
		return new self(
			typeModule: $params['typeModule'],
			typeCode: $params['typeCode'],
			id: $params['id'],
			phones: $params['phones'] ?? [],
		);
	}

	public function toArray(): array
	{
		return [
			'typeModule' => $this->typeModule,
			'typeCode' => $this->typeCode,
			'id' => $this->id,
			'phones' => $this->phones,
		];
	}
}
