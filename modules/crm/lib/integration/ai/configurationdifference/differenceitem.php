<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference;

final class DifferenceItem
{
	public function __construct(
		private readonly string|int $id,
		private readonly array $values,
	)
	{
	}

	public function id(): string|int
	{
		return $this->id;
	}

	public function values(): array
	{
		return $this->values;
	}

	public function value(string $field): mixed
	{
		return $this->values[$field] ?? null;
	}
}
