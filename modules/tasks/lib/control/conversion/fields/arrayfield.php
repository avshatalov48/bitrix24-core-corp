<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

use Bitrix\Tasks\Control\Conversion\ConvertableFieldInterface;
use Bitrix\Tasks\Control\Conversion\Field;
use Bitrix\Tasks\Control\Conversion\SubEntityFieldInterface;

abstract class ArrayField extends Field implements ConvertableFieldInterface, SubEntityFieldInterface
{
	public function convertValue(): array
	{
		$result = [];
		$key = $this->getConvertKey();
		foreach ($this->value as $item)
		{
			$value = $item[$key] ?? null;
			if (!empty($value))
			{
				$result[] = $value;
			}
		}

		return $result;
	}
}