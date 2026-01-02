<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

class UserField extends AbstractEntity
{
	public function __construct(
		public readonly string $key,
		public readonly array $value,
	)
	{
		if (!str_starts_with($this->key, 'UF_AUTO_'))
		{
			// todo: replace with meaningful exception
			throw new \Exception('unsupported userfield key');
		}
	}

	public function toArray(): array
	{
		return [
			'key' => $this->key,
			'value' => $this->value,
		];
	}

	public function getId(): mixed
	{
		return $this->key;
	}

	public static function mapFromArray(array $props): static
	{
		return new self(
			$props['key'],
			$props['value'],
		);
	}
}
