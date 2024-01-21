<?php

namespace Bitrix\BizprocMobile\Fields\Iblock;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Loader;

Loader::requireModule('iblock');

class Money extends \Bitrix\Iblock\BizprocType\Money
{
	public static function internalizeValue(FieldType $fieldType, $context, $value)
	{
		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
			&& is_array($value)
		)
		{
			return implode('|', $value);
		}

		return parent::internalizeValue($fieldType, $context, $value);
	}

	public static function externalizeValue(FieldType $fieldType, $context, $value)
	{
		if (
			defined('Bitrix\Bizproc\FieldType::VALUE_CONTEXT_JN_MOBILE')
			&& $context === FieldType::VALUE_CONTEXT_JN_MOBILE
			&& is_string($value)
		)
		{
			return [
				'amount' => explode('|', $value)[0],
				'currency' => explode('|', $value)[1],
			];
		}

		return parent::externalizeValue($fieldType, $context, $value);
	}
}