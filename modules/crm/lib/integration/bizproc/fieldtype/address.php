<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Bizproc\FieldType;

class Address extends UserFieldBase
{
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		global $USER_FIELD_MANAGER;

		$value = parent::extractValue($fieldType, $field, $request);
		$sType = static::getUserType($fieldType);

		$arUserFieldType = $USER_FIELD_MANAGER->GetUserType($sType);

		if (is_callable([$arUserFieldType['CLASS_NAME'], 'onbeforesave']))
		{
			$value = call_user_func_array(
				[$arUserFieldType['CLASS_NAME'], 'onbeforesave'],
				[$arUserFieldType, $value, 0]
			);
		}

		return $value;
	}

	public static function internalizeValue(FieldType $fieldType, $context, $value)
	{
		return htmlspecialcharsback($value);
	}
}
