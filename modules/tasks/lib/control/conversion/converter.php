<?php

namespace Bitrix\Tasks\Control\Conversion;

class Converter
{
	public static function fromSubEntityFormat(array $fields, array $remove = []): array
	{
		$result = [];
		foreach ($fields as $key => $value)
		{
			$field = Field::createByData($key, $value);
			$result[$field->getNormalizedKey()] = $field->convertValue();
			if (!$field->isSubEntity() || !in_array($field::getSubEntityKey(), $remove, true))
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}
}