<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill\Value;

class StringFieldValue extends BaseFieldValue
{
	public string $value;

	public function __construct($value)
	{
		$this->value = $value;
	}
}