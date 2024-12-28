<?php

namespace Bitrix\TasksMobile\UserField;

use Bitrix\TasksMobile\UserField\Field\BaseField;
use Bitrix\TasksMobile\UserField\Field\BooleanField;
use Bitrix\TasksMobile\UserField\Field\DateTimeField;
use Bitrix\TasksMobile\UserField\Field\DoubleField;
use Bitrix\TasksMobile\UserField\Field\StringField;

final class FieldFactory
{
	public static function createField(array $field): BaseField
	{
		$type = $field['USER_TYPE_ID'];
		$enumType = Type::tryFrom($type);
		$fieldClass = match ($enumType) {
			Type::Boolean => BooleanField::class,
			Type::DateTime => DateTimeField::class,
			Type::Double => DoubleField::class,
			Type::String => StringField::class,
			default => throw new \InvalidArgumentException("Unknown user field type: $type"),
		};

		return new $fieldClass([
			...$field,
			'TYPE' => $enumType,
		]);
	}
}
