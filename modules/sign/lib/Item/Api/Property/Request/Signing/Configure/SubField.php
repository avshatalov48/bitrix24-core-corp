<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

class SubField implements Contract\Item
{
	public function __construct(
		public string $type,
		public string $name,
		public ?string $label = null,
		public ?string $hint = null,
		public ?string $placeholder = null,
		public ?string $required = null,
	)
	{
	}

	public static function createFromFieldItem(Item\Field $field): static
	{
		return new static(
			$field->type,
			$field->name,
			$field->label,
			$field->hint,
			$field->placeHolder,
			$field->required
		);
	}
}