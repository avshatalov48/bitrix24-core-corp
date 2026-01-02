<?php

declare(strict_types=1);

namespace Bitrix\Crm\Dto\Booking;

use Bitrix\Main\Type\Contract\Arrayable;

class ExternalData implements Arrayable
{
	public function __construct(
		public readonly string $moduleId,
		public readonly string $entityTypeId,
		public readonly string $value,
	)
	{
	}

	public static function mapFromArray(array $clients): self
	{
		return new self(
			moduleId: $clients['moduleId'],
			entityTypeId: $clients['entityTypeId'],
			value: $clients['value'],
		);
	}

	public function toArray(): array
	{
		return [
			'moduleId' => $this->moduleId,
			'entityTypeId' => $this->entityTypeId,
			'value' => $this->value,
		];
	}
}
