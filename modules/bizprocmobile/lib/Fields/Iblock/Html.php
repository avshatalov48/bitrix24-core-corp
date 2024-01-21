<?php

namespace Bitrix\BizprocMobile\Fields\Iblock;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Loader;

Loader::requireModule('iblock');

class Html extends \Bitrix\Iblock\BizprocType\UserTypeProperty
{
	public static function externalizeValue(FieldType $fieldType, $context, $value)
	{
		if (is_array($value) && isset($value['TEXT']) && !is_scalar($value['TEXT']))
		{
			return is_object($value['TEXT']) && method_exists($value['TEXT'], '__toString')
				? (string)$value['TEXT']
				: '';
		}

		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
		)
		{
			return static::formatValuePrintable($fieldType, $value);
		}

		return parent::externalizeValue($fieldType, $context, $value);
	}

	public static function convertPropertyToView(FieldType $fieldType, int $viewMode, array $property): array
	{
		if ($viewMode === FieldType::RENDER_MODE_JN_MOBILE)
		{
			$property['Type'] = FieldType::TEXT;
		}

		return parent::convertPropertyToView($fieldType, $viewMode, $property);
	}
}
