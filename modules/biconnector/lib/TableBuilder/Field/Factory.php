<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

use Bitrix\Main\SystemException;
use Bitrix\Biconnector\ExternalSource\FieldType;

class Factory
{
	public static function getField(FieldType $type, string $name): Base
	{
		return match ($type)
		{
			FieldType::Int => new TypeInt($name),
			FieldType::String => new TypeText($name),
			FieldType::Double => new TypeDouble($name),
			FieldType::Date => new TypeDate($name),
			FieldType::DateTime => new TypeDateTime($name),
			FieldType::Money => new TypeDecimal($name),
			default => throw new SystemException("Unknown type {$type->value}"),
		};
	}
}
