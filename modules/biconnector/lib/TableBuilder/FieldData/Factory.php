<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

use Bitrix\Main\SystemException;
use Bitrix\Biconnector\ExternalSource\FieldType;

class Factory
{
	public static function getFieldData(FieldType $type, string $name, mixed $value): Base
	{
		return match ($type)
		{
			FieldType::Int => new DataInt($name, $value),
			FieldType::Double, FieldType::Money => new DataFloat($name, $value),
			FieldType::String => new DataText($name, $value),
			FieldType::Date => new DataDate($name, $value),
			FieldType::DateTime => new DataDateTime($name, $value),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}
}
