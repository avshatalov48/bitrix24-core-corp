<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

use Bitrix\Tasks\Control\Conversion\ConvertableFieldInterface;
use Bitrix\Tasks\Control\Conversion\Field;
use Bitrix\Tasks\Control\Conversion\SubEntityFieldInterface;

abstract class NumericField extends Field implements ConvertableFieldInterface, SubEntityFieldInterface
{
	public function convertValue(): int
	{
		return (int)($this->value[$this->getConvertKey()] ?? null);
	}
}