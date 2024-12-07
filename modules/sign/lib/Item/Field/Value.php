<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Contract;

class Value implements Contract\Item
{
	public function __construct(
		public int $fieldId,
		public ?int $id = null,
		public ?int $memberId = null,
		public ?string $text = null,
		public ?int $fileId = null,
		public ?bool $trusted = null,
	)
	{
	}
}