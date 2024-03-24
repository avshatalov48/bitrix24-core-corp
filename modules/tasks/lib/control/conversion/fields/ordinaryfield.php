<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

use Bitrix\Tasks\Control\Conversion\Field;

class OrdinaryField extends Field
{
	public function convertValue(): mixed
	{
		return $this->value;
	}

	public function getNormalizedKey(): string
	{
		return $this->key;
	}
}