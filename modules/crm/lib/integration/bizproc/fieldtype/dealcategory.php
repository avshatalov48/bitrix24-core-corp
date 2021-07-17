<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc;

class DealCategory extends Bizproc\BaseType\Select
{
	public static function getName(): string
	{
		return Loc::getMessage('CRM_BP_FIELDTYPE_DEAL_CATEGORY') ?: parent::getName();
	}

	protected static function getFieldOptions(Bizproc\FieldType $fieldType)
	{
		$options = \Bitrix\Crm\Category\DealCategory::getSelectListItems();

		return static::normalizeOptions($options);
	}

	public static function renderControlSingle(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	public static function renderControlMultiple(Bizproc\FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}
}
