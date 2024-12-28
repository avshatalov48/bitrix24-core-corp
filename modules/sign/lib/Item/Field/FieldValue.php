<?php

namespace Bitrix\Sign\Item\Field;

class FieldValue implements \Bitrix\Sign\Contract\Item
{
	public function __construct(
		public readonly string $fieldName,
		public readonly int $memberId,
		public readonly string $value,
		public ?int $id = null,
	) {}
}