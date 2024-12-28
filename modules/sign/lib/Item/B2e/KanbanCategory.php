<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Item;

final class KanbanCategory implements Item
{
	public function __construct(
		public string $code,
		public int $id,
		public bool $isDefault = false,
	) {}

	public static function fromArray(array $data): self
	{
		return new self(
			(string)($data['CODE'] ?? ''),
			(int)($data['ID'] ?? null),
			(string)($data['IS_DEFAULT'] ?? 'N') === 'Y',
		);
	}

	public function isDefault(): bool
	{
		return $this->isDefault ?? false;
	}
}